<?php
namespace app\index\controller;

use think\Request;

use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class Index extends \think\Controller
{

    // 文件上传表单
    public function index()
    {
        return $this->fetch();
    }

    // 文件上传提交
    public function up(Request $request)
    {
        // 获取表单上传文件
        $file = $request->file('file');
        if (empty($file)) {
            $this->error('请选择上传文件');
        }
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $this->success('文件上传成功：' . $info->getRealPath());
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }

    }
    //七牛云
    /**
     * 图片上传
     * @return String 图片的完整URL
     */
    public function upload()
    {
        if(request()->isPost()){
            $file = request()->file('file');
            // 要上传图片的本地路径
            $filePath = $file->getRealPath();
            //后缀
            $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
            // 上传到七牛后保存的文件名
            $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
            require_once VENDOR_PATH . 'qiniu/php-sdk/autoload.php';
            // 需要填写你的 Access Key 和 Secret Key
            $accessKey = config('ACCESSKEY');
            $secretKey = config('SECRETKEY');
            // 构建鉴权对象
            $auth = new Auth($accessKey, $secretKey);
            // 要上传的空间
            $bucket = config('BUCKET');
            $domain = config('DOMAIN');
            $token = $auth->uploadToken($bucket);
            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();
            // 调用 UploadManager 的 putFile 方法进行文件的上传
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            if ($err !== null) {
                return ["err"=>1,"msg"=>$err,"data"=>""];
            } else {
                //返回图片的完整URL
                return json(["err"=>0,"msg"=>"上传完成","data"=>$this ->download($ret['key'])]);
            }
        }
    }
    //七牛云
    /**
     * 图片下载
     * @return String 图片的完整URL
     */
    private function download($key)
    {
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = config('ACCESSKEY');
        $secretKey = config('SECRETKEY');
        $domain = config('DOMAIN');
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        //baseUrl构造成私有空间的域名/key的形式
        $baseUrl = 'http://'.$domain.'/'.$key;
        $authUrl = $auth->privateDownloadUrl($baseUrl);
        return $authUrl;
    }
}