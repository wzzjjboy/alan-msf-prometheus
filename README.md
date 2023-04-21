# msf框架接入prometheus



接入步骤：

1. 定义prometheus使用的redis配置

```php
$config['redis']['prometheus']['ip']               = '127.0.0.1';
$config['redis']['prometheus']['port']             = 6379;
$config['redis']['prometheus']['select']           = 0;
$config['redis']['prometheus']['password']         = '123456';
```

2. 引入prometheus组件，在入口文件(server.php)添加如下代码

   ```php
   \alan\msf_prometheus\di\ClassLoader::init();
   ```

3. 添加支持/metrics路由，创建控制器 app/Controllers/Metrics.php

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

4. 指标示例

   ```properties
   # HELP http_server_requests_code_total http server requests count.
   # TYPE http_server_requests_code_total counter
   http_server_requests_code_total{path="/metrics",code="",method="GET"} 16170
   # TYPE http_server_requests_duration_ms histogram
   http_server_requests_duration_ms_bucket{path="/metrics",le="25"} 16124
   http_server_requests_duration_ms_bucket{path="/metrics",le="50"} 16162
   http_server_requests_duration_ms_bucket{path="/metrics",le="100"} 16168
   http_server_requests_duration_ms_bucket{path="/metrics",le="250"} 16170
   http_server_requests_duration_ms_bucket{path="/metrics",le="500"} 16170
   http_server_requests_duration_ms_bucket{path="/metrics",le="1000"} 16170
   http_server_requests_duration_ms_bucket{path="/metrics",le="1500"} 16170
   http_server_requests_duration_ms_bucket{path="/metrics",le="2000"} 16170
   http_server_requests_duration_ms_bucket{path="/metrics",le="3000"} 16170
   http_server_requests_duration_ms_bucket{path="/metrics",le="+Inf"} 16170
   http_server_requests_duration_ms_count{path="/metrics"} 16170
   http_server_requests_duration_ms_sum{path="/metrics"} 49693
   ```

5. 尽情使用...
