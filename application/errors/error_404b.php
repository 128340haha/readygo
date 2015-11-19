<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<!-- Required Stylesheets -->
<?= link_tag('assets/business/css/reset.css'); ?> 
<?= link_tag('assets/business/css/fonts/ptsans/stylesheet.css'); ?> 
<?= link_tag('assets/business/css/fluid.css'); ?> 

<?= link_tag('assets/business/css/mws.style.css'); ?> 
<?= link_tag('assets/business/css/icons/icons.css'); ?> 

<!-- Theme Stylesheet -->
<?= link_tag('assets/business/css/mws.theme.css'); ?> 

<title>找不到该页</title>

</head>

<body> 
    <div id="mws-wrapper">       
        <div id="mws-container" style="margin-left:0px;" class="clearfix">
            <div class="container" >
            	<div id="mws-error-container">
                	<h1 id="mws-error-code">Error <span>404</span></h1>
                    <p style="font-size:24px; font-weight:bold;">没有找到您访问的页面</p>
                    <p id="mws-error-message"><a style="text-decoration:none; color:#323232;" href="javascript:history.go(-1)">点我返回上一页</a></p>
                </div>
            </div>            
		</div>
    </div>
</body>
</html>
