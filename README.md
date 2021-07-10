# card-system-usdtpay 风铃发卡USDT支付插件 用户直接支付到个人地址，不用经过第三方，实时到账
### 网站配置
 - 通过GitHub下载得到app和public文件 直接覆盖到风铃发卡系统根目录即可，然后按照下面配置修改参数即可使用Token188 USDT支付。

### 子渠道配置
 - 选择管理中心
 - 支付渠道
 - 添加子渠道
 - 名称 Token188
 - 驱动 Token188 (填写错误会找不到驱动)
 - 方式 Token188 数字货币
 - 支付图片路径填 /plugins/images/xxxx.png 前面一定要加斜杠不然图片加载不了
 - app_id, api_secret  请到[TOKEN188](https://www.token188.com/) 官网注册获取.

### 产品介绍

 - [TOKEN188 USDT支付平台主页](https://www.token188.com)
 - [TOKEN188钱包](https://www.token188.com)（即将推出）
 - [商户平台](https://www.token188.com/manager)
### 特点
 - 使用您自己的USDT地址收款没有中间商
 - 五分钟完成对接
 - 没有任何支付手续费

## 安装流程
1. 注册[TOKEN188商户中心](https://www.token188.com/manager)
2. 在商户中心添加需要监听的地址
3. 根据使用的不同面板进行回调设置(回调地址可以不填)


## 有问题和合作可以小飞机联系我们
 - telegram：@token188
