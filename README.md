## **安装使用**
### **环境要求**
```
PHP >= PHP7.3
Mysql >= 5.5.0 (需支持innodb引擎)
Apache 或 Nginx
PDO PHP Extension
MBstring PHP Extension
CURL PHP Extension
Node.js (可选,用于安装Bower和LESS,同时打包压缩也需要使用到)
Composer (可选,用于管理第三方扩展包)
Bower (可选,用于管理前端资源)
Less (可选,用于编辑less文件,如果你需要增改css样式,最好安装上)
```
### **安装**
1. 下载前端插件依赖包  
`bower install`
2. 下载PHP依赖包  
`composer install`
3. 导入数据库  muban.sql

4. nginx配置伪静态

location / {
    index  index.html index.htm index.php;
    if (!-e $request_filename) {
        rewrite  ^(.*)$  /index.php?s=/$1  last;
        break;
    }
}

5. api文档生成

apidoc -i application/api/apidoc/ -o public/apidoc/

生成完 访问路径为 http://***.com/apidoc