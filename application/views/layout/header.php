<?php
/*
 * Created: 02.03.2013
 * Updated: 02.03.2013
 * Created by: Martin Lazarov
 *
 * Changelog:
 */
$version = $this->config->item('website_version');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
	<title><?php echo $this->config->item('website_name');?> :: <?php echo $this->title;?></title>
<?php
if($this->description){
	echo '<meta name="description" content="'.$this->description.'">'."\n";
}
if($this->keywords){
	echo '<meta name="keywords" content="'.$this->keywords.'">'."\n";
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="index,follow" >
<meta name="generation" content="<?php echo time();?>" >
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="alternate" href="<?php echo site_url('feed');?>" type="application/rss+xml" title="News">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" >
<link href="/css/bootstrap.css?ver=<?php echo $version;?>" rel="stylesheet">
<?php if(!$this->flat){?>
<style>
  body {
    padding-top: 45px; /* 60px to make the container go all the way to the bottom of the topbar */
  }
  .sidebar-nav {
        padding: 9px 0;
  }
</style>
<?php } ?>
<link href="/css/bootstrap-responsive.css?ver=<?php echo $version;?>" rel="stylesheet">
<link href="/css/custom.css?ver=<?php echo $version;?>" rel="stylesheet">


<?php
if(is_array($this->css)){
	foreach($this->css as $file){
		echo '<link rel="stylesheet" href="'.base_url().'css/'.$file.'.css?ver='.$version.'" type="text/css" />'."\n";
	}
}elseif($this->css){
	echo '<link rel="stylesheet" href="'.base_url().'css/'.$this->css.'.css?ver='.$version.'" type="text/css" />'."\n";
}

?>
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="/js/jquery-ui-1.10.1.custom.min.js"></script>
<script type="text/javascript" src="/js/custom.js?ver=<?php echo $version;?>"></script>
<script type="text/javascript" src="/js/bootstrap.js?ver=<?php echo $version;?>"></script>



<?php
if(is_array($this->js)){
	foreach($this->js as $file){
		echo '<script type="text/javascript" src="'.base_url().'js/'.$file.'.js?ver='.$version.'"></script>'."\n";
	}
}elseif($this->js){
	echo '<script type="text/javascript" src="'.base_url().'js/'.$this->js.'.js?ver='.$version.'"></script>'."\n";
}

$uri_string = $this->router->uri->uri_string;
?>

</head>
<body>
<?php if(!$this->flat){?>
<div class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container-fluid">
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			<a class="brand" href="/"><?php echo $this->config->item('website_name');?> <i><?php echo "alfa";//echo 'v'.$version;?></i></a>
			<div class="btn-group pull-right">
				<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="icon-user"></i> Account
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<?php
					if($this->session->userdata('id')){ ?>
					<li><a href="/profile/">Profile</a></li>
					<li class="divider"></li>
					<li><a href="/profile/logout/">Sign Out</a></li>
					<?php
					}else{ ?>
					<li><a href="/profile/login/">Login</a></li>
					<li class="divider"></li>
					<li><a href="/profile/register/">Register</a></li>
					<?php
					}
					?>
				</ul>
			</div>

			<?php
			if($this->session->userdata('id')){ ?>
			<div style="float:right;padding-right:10px;">
				<a href="/blog/create/" class="btn">Add Blog Project</a>
			</div>
			<?php } ?>

			<div class="nav-collapse">
				<ul class="nav">
				<?php
				if(!$this->session->userdata('id')){ ?>
					<li<?php echo ($uri_string==''?' class="active"':'');?>><a href="/">Home</a></li>
					<li<?php echo ($uri_string=='profile/login'?' class="active"':'');?>><a href="/profile/login/">Login</a></li>
					<li<?php echo ($uri_string=='profile/register'?' class="active"':'');?>><a href="/profile/register/">Register</a></li>
				<?php } else{ ?>
					<li<?php echo ($uri_string==''?' class="active"':'');?>><a href="/">Dashboard</a></li>
					<?php /*<li<?php echo ($uri_string=='blogs'?' class="active"':'');?>><a href="/blogs/">Blogs</a></li>*/?>
				<?php } ?>
					<!--li<?php echo ($uri_string=='about'?' class="active"':'');?>><a href="/about/">About</a></li//-->
					<li<?php echo ($uri_string=='contact'?' class="active"':'');?>><a href="/contact/">Contact</a></li>
					<!--li<?php echo ($uri_string=='terms'?' class="active"':'');?>><a href="/terms/">Terms</a></li//-->
				</ul>
			</div><!--/.nav-collapse -->
		</div>
	</div>
</div>
<?php }?>

