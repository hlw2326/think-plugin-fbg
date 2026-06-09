<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use think\admin\Storage;
use think\admin\storage\AliossStorage;
use think\admin\storage\LocalStorage;
use think\admin\storage\QiniuStorage;
use think\exception\HttpResponseException;
use think\Request;

/**
 * 文件上传 API
 * @class Upload
 * @package plugin\fbg\controller\api\v1
 */
class Upload extends Auth
{
    private const BIZ_CONFIG = [
        'avatar' => [
            'prefix' => 'avatar',
            'exts' => ['jpg', 'jpeg', 'png', 'webp'],
            'maxSize' => 2 * 1024 * 1024,
        ],
    ];

    public function sign(): void
    {
        $data = $this->_vali([
            'biz.require' => '缺少业务标识',
            'ext.require' => '缺少扩展名',
            'size.default' => 0,
        ]);

        $biz = (string) $data['biz'];
        if (!isset(self::BIZ_CONFIG[$biz])) {
            $this->error('不支持的业务类型');
        }
        $cfg = self::BIZ_CONFIG[$biz];

        $ext = strtolower(trim((string) $data['ext'], '.'));
        if (!in_array($ext, $cfg['exts'], true)) {
            $this->error('不支持的文件类型');
        }
        if (intval($data['size']) > $cfg['maxSize']) {
            $maxMb = intval($cfg['maxSize'] / 1024 / 1024);
            $this->error("文件不能超过 {$maxMb}MB");
        }

        $seed = $this->userId . '-' . microtime(true) . '-' . mt_rand();
        $key = Storage::name($seed, $ext, $cfg['prefix'], 'md5');
        $type = strtolower(sysconf('storage.type|raw')) ?: 'local';

        try {
            if ($type === 'local') {
                $this->error('未设置上传接口');
            } elseif ($type === 'alioss') {
                $alioss = AliossStorage::instance();
                $token = $alioss->token($key, 3600);
                $this->success('ok', [
                    'type' => 'alioss',
                    'key' => $key,
                    'url' => $token['siteurl'],
                    'server' => $alioss->upload(),
                    'OSSAccessKeyId' => $token['keyid'],
                    'policy' => $token['policy'],
                    'Signature' => $token['signature'],
                    'success_action_status' => '200',
                ]);
            } elseif ($type === 'qiniu') {
                $qiniu = QiniuStorage::instance();
                $this->success('ok', [
                    'type' => 'qiniu',
                    'key' => $key,
                    'url' => $qiniu->url($key, false),
                    'server' => $qiniu->upload(),
                    'token' => $qiniu->token($key, 3600),
                ]);
            } else {
                $this->error("暂未接入 {$type} 存储 of 直传");
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function put(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式不支持');
        }
        if (strtolower(sysconf('storage.type|raw') ?: 'local') === 'local') {
            $this->error('未设置上传接口');
        }

        try {
            $key = (string) $this->request->post('key', '');
            if (empty($key) || strpos($key, '..') !== false) {
                $this->error('非法的上传路径');
            }

            $matched = null;
            foreach (self::BIZ_CONFIG as $cfg) {
                if (strpos($key, $cfg['prefix'] . '/') === 0) {
                    $matched = $cfg;
                    break;
                }
            }
            if ($matched === null) {
                $this->error('非法的上传路径');
            }

            $file = $this->request->file('file');
            if (empty($file)) {
                $this->error('读取临时文件失败');
            }
            if ($file->getSize() > $matched['maxSize']) {
                $maxMb = intval($matched['maxSize'] / 1024 / 1024);
                $this->error("文件不能超过 {$maxMb}MB");
            }

            $extension = strtolower($file->getOriginalExtension());
            if (!in_array($extension, $matched['exts'], true)) {
                $this->error('不支持的文件类型');
            }
            if (strtolower(pathinfo($key, PATHINFO_EXTENSION)) !== $extension) {
                $this->error('文件后缀与凭证不匹配');
            }

            $local = LocalStorage::instance();
            $distName = $local->path($key, false);
            if (PHP_SAPI === 'cli') {
                is_dir(dirname($distName)) || mkdir(dirname($distName), 0777, true);
                rename($file->getPathname(), $distName);
            } else {
                $file->move(dirname($distName), basename($distName));
            }

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                if ($this->imgNotSafe($distName) && $local->del($key)) {
                    $this->error('图片未通过安全检查');
                }
                [$width, $height] = getimagesize($distName);
                if (($width < 1 || $height < 1) && $local->del($key)) {
                    $this->error('读取图片尺寸失败');
                }
            }

            $info = $local->info($key, false, $file->getOriginalName());
            if (empty($info['url'])) {
                $this->error('文件处理失败');
            }

            $this->success('上传成功', ['url' => $info['url']]);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public static function allowedHosts(Request $request): array
    {
        $type = strtolower(sysconf('storage.type|raw')) ?: 'local';
        $hosts = [];
        try {
            if ($type === 'local') {
                $hosts[] = $request->host();
                $hosts[] = sysconf('storage.local_http_domain|raw') ?: '';
            } elseif ($type === 'alioss') {
                $hosts[] = sysconf('storage.alioss_http_domain|raw') ?: '';
                $hosts[] = parse_url(AliossStorage::instance()->upload(), PHP_URL_HOST);
            } elseif ($type === 'qiniu') {
                $hosts[] = sysconf('storage.qiniu_http_domain|raw') ?: '';
                $hosts[] = parse_url(QiniuStorage::instance()->url('_probe'), PHP_URL_HOST);
            }
        } catch (\Exception $exception) {
            // 存储未配置时仅返回已收集到的 host。
        }
        return array_values(array_filter(array_unique($hosts)));
    }

    private function imgNotSafe(string $filename): bool
    {
        $source = fopen($filename, 'rb');
        if (($size = filesize($filename)) > 512) {
            $hexs = bin2hex(fread($source, 512));
            fseek($source, $size - 512);
            $hexs .= bin2hex(fread($source, 512));
        } else {
            $hexs = bin2hex(fread($source, $size));
        }
        if (is_resource($source)) {
            fclose($source);
        }
        $bins = hex2bin($hexs);
        foreach (['<?php ', '<% ', '<script '] as $key) {
            if (stripos($bins, $key) !== false) {
                return true;
            }
        }
        $result = preg_match('/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is', $hexs);
        return $result === false || $result > 0;
    }
}
