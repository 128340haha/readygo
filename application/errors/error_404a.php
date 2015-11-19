<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>404 - 错误页面</title>
	<style type="text/css">
	*{margin:0;padding:0;}
	body{background:#F8F8F8;font-size:14px;}
	.main_outer{background: url(<?= base_url('assets/public/images'); ?>/1362920104872409008969.png) no-repeat center 310px;width:1050px;margin:50px auto;}
	.main{background:url(<?= base_url('assets/public/images'); ?>/not-found.png) no-repeat 57px -20px;}
	.main_inner{background:url(<?= base_url('assets/public/images'); ?>/shadow.gif) no-repeat center 363px;padding-bottom: 50px;}
	.tips{height:310px;background:url(<?= base_url('assets/public/images'); ?>/cup.png) no-repeat 758px bottom;padding:15px 25px;font-family:microsoft yahei;padding-right:185px;padding-top:50px;}
	.tips p{margin-left:540px;margin-bottom:15px;}
	.tips a{text-decoration:none;color:#333;}
	.tips h1{margin-left:540px;margin-bottom:10px;font-size:16px;}
	.tips ul{width:127px;margin:0 auto 0 562px;text-align:left;}
	</style>
</head>
<body>
<div class="main_outer">
	<div class="main">
		<div class="main_inner">
			<div class="tips">
				<p>没有发现您查找的页面！<br />出错了哟~点此返回网站<a href="<?= base_url('admin/setting/index') ?>" title="欢迎页"/>欢迎页</a></p>
				<h1><a href="#" title="MUED模板站">深圳声物科技</a></h1>
				<ul>
					<li><a href="#" title="智能家居">智能家居</a></li>
					<li><a href="#" title="运动器材">运动器材</a></li>
					<li><a href="#" title="家庭娱乐">家庭娱乐</a></li>
				</ul>
				<div class="shadow"></div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
