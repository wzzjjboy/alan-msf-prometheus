# msf框架接入prometheus



接入步骤：

1. 定义常量 APP_NAME 定义在server.php,用于区分不同的应用

```php
defined('APP_NAME') or define('APP_NAME', 'msf_fwb');
```

2. 定义prometheus使用的redis配置

```php
$config['redis']['prometheus']['ip']               = '127.0.0.1';
$config['redis']['prometheus']['port']             = 6379;
$config['redis']['prometheus']['select']           = 0;
$config['redis']['prometheus']['password']         = '123456';
```

3. 引入prometheus组件，在入口文件(server.php)添加如下代码

   ```php
   \alan\msf_prometheus\di\ClassLoader::init();
   ```

4. 添加支持/metrics路由，创建控制器 app/Controllers/Metrics.php

   ```php
   <?php
   
   namespace App\Controllers;
   
   use alan\msf_prometheus\prometheus\MetricsTrait;
   use PG\MSF\Controllers\Controller;
   
   class Metrics extends Controller
   {
       use MetricsTrait;
   
       public function actionIndex(){
           $result = yield $this->metricsOutput();
           $this->getContext()->getOutput()->setContentType("text/plain; version=0.0.4;charset=UTF-8");
           $this->getContext()->getOutput()->end($result);
       }
   }
   ```

   5. 指标示例

      ```properties
      # HELP msf_fwb_http_requests http requests histogram!
      # TYPE msf_fwb_http_requests histogram
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.005"} 16
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.01"} 16
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.025"} 23
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.05"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.075"} 31
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.1"} 47
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.25"} 64
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.5"} 66
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.75"} 66
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="1"} 66
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="2.5"} 67
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="5"} 67
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="7.5"} 67
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="10"} 67
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="+Inf"} 67
      msf_fwb_http_requests_count{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2"} 67
      msf_fwb_http_requests_sum{request_status="200",request_type="HTTP",request_path="/metrics",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2"} 6.10571765899655348
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.005"} 13
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.01"} 17
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.025"} 19
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.05"} 20
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.075"} 21
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.1"} 23
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.25"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.5"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="0.75"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="1"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="2.5"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="5"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="7.5"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="10"} 24
      msf_fwb_http_requests_bucket{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2",le="+Inf"} 24
      msf_fwb_http_requests_count{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2"} 24
      msf_fwb_http_requests_sum{request_status="200",request_type="HTTP",request_path="/test/push2",request_method="GET",hostname="0ce1d43383b7",instance="0ce1d43383b7:9000",ip="172.18.0.2"} 0.5322232246398948
      # HELP msf_fwb_http_requests_totoal http requests counter
      # TYPE msf_fwb_http_requests_totoal counter
      msf_fwb_http_requests_totoal{requests_uri="/metrics",request_method="GET"} 10
      msf_fwb_http_requests_totoal{requests_uri="/test/push2",request_method="GET"} 7
      # HELP msf_fwb_some_metric this is for testing
      # TYPE msf_fwb_some_metric gauge
      msf_fwb_some_metric{foo="lalal",bar="lululu"} 123
      ```

      

   6. 尽情使用...
