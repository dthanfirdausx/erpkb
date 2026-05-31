<?php 
$doc_template = '
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>'.ucwords($service_name).' Rest API Documentation</title>
    <meta content=\'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no\' name=\'viewport\'>
    <!-- Bootstrap 3.3.2 -->
    <link href="../../admina/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
     <link href="../../admina/assets/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Ionicons -->
    <link href="../../admina/assets/plugins/ionic/css/ionicons.min.css" rel="stylesheet" type="text/css" />

     <link href="../../admina/assets/plugins/google-code-prettify/prettify.css" rel="stylesheet" type="text/css" />

    <!-- Theme style -->
    <link href="../../admina/assets/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
    <link href="../../admina/assets/dist/css/skins/skin-blue.min.css" rel="stylesheet" type="text/css" />
 <style type="text/css">
  .content-wrapper p{padding:0 10px;font-size:16px}#components>h3{font-size:25px;color:#000}#components>h4{font-size:20px;color:#000}ul{margin-bottom:20px}.page-header{margin:20px 0;position:relative;z-index:1;font-size:30px}.page-header a,.page-header span{z-index:5;display:block;background-color:#ecf0f5;color:#000}.page-header a::before,.page-header span::before{content:\'#\';font-size:25px;margin-right:10px;color:#3c8dbc}#components>h3:before,.page-header:before{display:block;content:" ";margin-top:-60px;height:60px;visibility:hidden}.lead{font-size:18px;font-weight:400}.eg{position:absolute;top:0;left:0;display:inline-block;background:#d2d6de;padding:5px;border-bottom-right-radius:3px;border-top-left-radius:3px;border-bottom:1px solid #d2d6dc;border-right:1px solid #d2d6dc}.eg+*{margin-top:30px}.content{padding:10px 25px}.hierarchy{background:#333;color:#fff}.plugins-list li{width:50%;float:left}pre{border:none}.sidebar{margin-top:0;padding-top:0!important}.box .main-header{z-index:1000;position:relative}.treeview .nav li a:active,.treeview .nav li a:hover{background:0 0}p{padding:0!important}.pln{color:#111}@media screen{.kwd,.str{color:#739200}.com{color:#999}.typ{color:#f05}.lit{color:#538192}.clo,.opn,.pun,.tag{color:#111}.atn{color:#739200}.atv{color:#f05}.dec,.var{color:#111}.fun{color:#538192}}@media print,projection{.kwd,.tag,.typ{font-weight:700}.str{color:#060}.kwd{color:#006}.com{color:#600;font-style:italic}.typ{color:#404}.lit{color:#044}.clo,.opn,.pun{color:#440}.tag{color:#006}.atn{color:#404}.atv{color:#060}}ol.linenums{margin-top:0;margin-bottom:0}
  .table {
    border: 1px solid #000000;
  }
  .table-bordered > thead > tr > th,
  .table-bordered > tbody > tr > th,
  .table-bordered > tfoot > tr > th,
  .table-bordered > thead > tr > td,
  .table-bordered > tbody > tr > td,
  .table-bordered > tfoot > tr > td {
     border: 1px solid #000000;
  }
  .dl-horizontal dt {
    text-align: left;
  }
</style>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn\'t work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
  </head>
  <body class="skin-blue fixed" data-spy="scroll" data-target="#scrollspy">
    <div class="wrapper">

      <header class="main-header">
        <!-- Logo -->
        <a style="font-size: 13px;" href="<?=base_url();?>api/'.$service_name.'/doc" class="logo"><b>'.ucwords($service_name).' Restful API</b></a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
          <!-- Sidebar toggle button-->
          <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
          </a>

        </nav>
      </header>
      <!-- Left side column. contains the logo and sidebar -->
      <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar" id="scrollspy">

          <!-- sidebar menu: : style can be found in sidebar.less -->
          <ul class="nav sidebar-menu">
            <li class="header">TABLE OF CONTENTS</li>
             <li><a href="#overview"><i class=\'fa fa-circle-o\'></i> Overview</a></li>
            <li><a href="#status"><i class=\'fa fa-circle-o\'></i> API Status</a></li>
             <li><a href="#method"><i class=\'fa fa-circle-o\'></i> API Method</a></li>
            <li><a href="#list_user"><i class=\'fa fa-circle-o\'></i> GET /api/'.$service_name.'</a></li>
            <li><a href="#detail_user"><i class=\'fa fa-circle-o\'></i> GET /api/'.$service_name.'/:id</a></li>
            <li><a href="#search"><i class=\'fa fa-circle-o\'></i> GET /api/'.$service_name.'/search/:search keyword</a></li>
            <li><a href="#token"><i class=\'fa fa-circle-o\'></i> Key Token</a></li>
            <li><a href="#post_user"><i class=\'fa fa-circle-o\'></i> POST /api/'.$service_name.'</a></li>
            <li><a href="#update"><i class=\'fa fa-circle-o\'></i> PUT /api/'.$service_name.'/update/:id</a></li>
            <li><a href="#delete"><i class=\'fa fa-circle-o\'></i> DELETE /api/delete/:id</a></li>



          </ul>
        </section>
        <!-- /.sidebar -->
      </aside>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
          <h1>
            '.ucwords($service_name).' Rest API Documentation
            <small>Current version 1.0</small>
          </h1>
          <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Documentation</li>
          </ol>
        </div>

        <!-- Main content -->
        <div class="content body" style="padding-bottom:30%">
<section id=\'overview\'>
  <h2 class=\'page-header\'><a href="#introduction">Overview</a></h2>
  <p class=\'lead\'>
    This is auto-generated document for <b>'.$service_name.' Rest API</b> that you can use to query '.$service_name.' data, search '.$service_name.', update '.$service_name.', and delete '.$service_name.' data.
  </p>
</section><!-- /#introduction -->

<section id="status">
  <h2 class=\'page-header\'><a href="#status">API Status</a></h2>
<p class=\'lead\'>
  <h3>Each time Request is performed, API will return the status whether it\'s success or not <a class="headerlink" href="#defining-the-api" title="Permalink to this headline"></a></h3>
    <p>Below is the list of response\'s status when request is performed</p>
    <table border="1" class="table table-bordered">
    <colgroup>
    <col width="50%">
    <col width="9%">
    <col width="30%">
    </colgroup>
    <thead valign="bottom">
    <tr class="row-odd"><th class="head">Type Of Status</th>
    <th class="head">Status Code</th>
    <th class="head">Description</th>
    </tr>
    </thead>
    <tbody valign="top">
    <tr class="row-even"><td>The request success or Data found</td>
    <td>200</td>
    <td>Ok</td>
    </tr>
     <tr class="row-even"><td>Successfuly post,update or delete the data</td>
    <td>201</td>
    <td>Created,updated,deleted Sucessfully</td>
    </tr>
    <tr class="row-even"><td> Error code for a missing header authentication token</td>
    <td>400</td>
    <td>Api key is misssing</td>
    </tr>
      <tr class="row-even"><td> Error code for invalid authentication token</td>
    <td>401</td>
    <td>Access Denied. Invalid Api key</td>
    </tr>
    <tr class="row-even"><td>No Data found</td>
    <td>404</td>
    <td>Nothing Found</td>
    </tr>
    <tr class="row-even"><td>The request was well-formed but was unable to process the contained instructions due to semantic errors</td>
    <td>422</td>
    <td>Unprocessable Entity</td>
    </tr>
    </tbody>
    </table>
</p>
</section>

<section id="method">
  <h2 class="page-header"><a href="#method">API Method</a></h2>
  <p class="lead"></p>
    <div class="section" id="defining-the-api">
    <p>The API consists of the following methods:</p>
    <table border="1" class="table table-bordered">
    <colgroup>
    <col width="9%">
    <col width="30%">
    <col width="62%">
    </colgroup>
    <thead valign="bottom">
    <tr class="row-odd"><th class="head">Method</th>
    <th class="head">URL</th>
    <th class="head">Action</th>
    </tr>
    </thead>
    <tbody valign="top">
    <tr class="row-even"><td>GET</td>
    <td><?=base_url();?>api/'.$service_name.'</td>
    <td>Retrieve all '.$service_name.'</td>
    </tr>
     <tr class="row-even"><td>GET</td>
    <td><?=base_url();?>api/'.$service_name.'/1</td>
    <td>Retrieve single '.$service_name.' based on primary key</td>
    </tr>
    <tr class="row-odd"><td>GET</td>
    <td><?=base_url();?>api/'.$service_name.'/search/search keyword</td>
    <td>Search for '.$service_name.' with keyword \'search keyword’</td>
    </tr>
    <tr class="row-odd"><td>POST</td>
    <td><?=base_url();?>api/'.$service_name.'</td>
    <td>Add or post new '.$service_name.'</td>
    </tr>
    <tr class="row-even"><td>PUT</td>
    <td><?=base_url();?>api/'.$service_name.'/update/1</td>
    <td>Update '.$service_name.' data</td>
    </tr>
    <tr class="row-odd"><td>DELETE</td>
    <td><?=base_url();?>api/'.$service_name.'/delete/1</td>
    <td>Delete '.$service_name.' data based on primary key</td>
    </tr>
    </tbody>
    </table>
    </div>
</section>

          <!-- ============================================================= -->

<section id=\'list_user\'>
  <h2 class=\'page-header\'><a href="#list_user">GET /api/'.$service_name.'</a></h2>
  <p class=\'lead\'>
    Return all '.$service_name.' data. By default Json response will show 10 data per page, but you can add \'perpage\' parameter on url to show less or more than 10 data. and alswo to show the next 10 records, you can add \'page\' parameter on url<br>
  </p>
<h3>Resource URL</h3>
<h4><?=base_url();?>api/'.$service_name.'</h4>
Go to page 3 by adding \'page\' parameter  
<h4><?=base_url();?>api/'.$service_name.'?page=3</h4>
Go to page 3 and show 15 data with \'perpage\' parameter
<h4><?=base_url();?>api/'.$service_name.'?page=3&perpage=15</h4>

<h3>Example Result</h3>
    <pre class="prettyprint">
{  
   status:{  
      code:200,
      message:"Ok"
   },
   meta:{  
      total-records:15,
      current-records:3,
      total-pages:5,
      current-page:1
   },
   results:[  
   		'.$data_req.'
   ],
   paginations:{  
      self:"<?=base_url();?>api/'.$service_name.'?page=1&perpage=3",
      first:"<?=base_url();?>api/'.$service_name.'?page=1&perpage=3",
      prev:"<?=base_url();?>api/'.$service_name.'?page=1&perpage=3",
      next:"<?=base_url();?>api/'.$service_name.'?page=2&perpage=3",
      last:"<?=base_url();?>api/'.$service_name.'?page=172&perpage=3"
   }
}
</pre>
<ul>
  <li>total-records  	: All record available</li>
  <li>current-records   : Total Current Page records</li>
  <li>total-pages   	: Total Pages available, this total pages depends on how many records to show perpage</li>
  <li>current-page   	: Current active page</li>
</ul>
</section>

<section id="detail_user">
  <h2 class="page-header"><a href="#detail_user">GET /api/'.$service_name.'/:id</a></h2>
  <p class="lead">Show Detail Single '.$service_name.'</p>

<h3>Resource URL</h3>
<h4><?=base_url();?>api/'.$service_name.'/:id</h4>
<h3>Example Result</h3>
<h4><?=base_url();?>api/'.$service_name.'/107</h4>
    <pre class="prettyprint">
    {
    status: {
        code: 200,
        message: "Ok"
    },
    results: [{
        '.$view_single_req.'
    }]
}
</pre>
</section>

<section id="search">
  <h2 class="page-header"><a href="#search">GET /api/'.$service_name.'/search/:search keyword</a></h2>
  <p class="lead">Returns a collection of relevant '.$service_name.' matching a specified query.</p>

<h3>Resource URL</h3>
<h4><?=base_url();?>api/'.$service_name.'/search/:search keyword</h4>
<h3>Example Result</h3>
<h4><?=base_url();?>api/'.$service_name.'/search/search keyword</h4>
    <pre class="prettyprint">
{
    status: {
        code: 200,
        message: "Ok"
    },
    results: [
    	'.$data_req.'
    ],
    paginations: {
        self: "<?=base_url();?>api/'.$service_name.'/search/search keyword?page=1",
        first: "<?=base_url();?>api/'.$service_name.'/search/search keyword?page=1",
        prev: "<?=base_url();?>api/'.$service_name.'/search/search keyword?page=1",
        next: "<?=base_url();?>api/'.$service_name.'/search/search keyword?page=1",
        last: "<?=base_url();?>api/'.$service_name.'/search/search keyword?page=1"
    }
}s
</pre>
Response structure is equal to get '.$service_name.'
</section>


<section id="token">
  <h2 class="page-header"><a href="#token">Token</a></h2>
  <p class="lead">If you enable authentication on create,read,update or delete, then the api key should be included in the request header with \'Authentication\' paramater and it\'s token value</p>

<h3>Get Token</h3>
In order to get unique token, request post to url below with username and password parameter.
<h3>Resource URL</h3>
<h4><?=base_url();?>api/'.$service_name.'/login</h4>
<h3>Parameters</h3>

<dl class="dl-horizontal">
 <dt>username</dt>
  <dd>Your username</dd>
  <dt>password</dt>
  <dd>Your Password</dd>
  </dl>

On successful login the following json will be issued. 
This could be permanent or temporary token depending on token expiration setting.
    <pre class="prettyprint">
{
  "user": "wildan tea",
  "token": "3bc5be35c35cf787e4fc530999381e9d"
}
</pre>
If the credentials are wrong, you can expect the following json.
    <pre class="prettyprint">
{
  "status": {
    "error": true,
    "code": 401,
    "message": "Invalid Username or Password"
  }
}
</pre>
</section>


<section id=\'post_user\'>
  <h2 class=\'page-header\'><a href="#post_user">POST /api/'.$service_name.'</a></h2>
  <p class=\'lead\'>
    Create / Post New '.$service_name.'<br>
  </p>
<h3>Resource URL</h3>
<h4><?=base_url();?>api/'.$service_name.'</h4>
<h3>Parameters</h3>
Add header request with token value if you were previously enable the authentication, otherwise just send the body request.
<h4>Request Header</h4>
<table border="1" class="table table-bordered">
        <colgroup>
    <col width="20%">
    <col width="80%">
    </colgroup>
    <thead valign="bottom">
    <tr class="row-odd"><th class="head">Parameters</th>
    <th class="head">Description</th>
    </tr>
    </thead>
    <tbody valign="top">
    <tr class="row-even"><td>Authorization</td>
    <td>Secret Key Token</td>
    </tr>
 	</tbody>
</table>
<h4>Request Body</h4>
<table border="1" class="table table-bordered">
    <colgroup>
    <col width="25%">
    <col width="25%">
    <col width="50%">
    </colgroup>
    <thead valign="bottom">
    <tr class="row-odd">
    <th class="head">Parameters</th>
    <th class="head">Validation Type</th>
    <th class="head">Description</th>
    </tr>
    </thead>
    <tbody valign="top">
    '.$post_components.'
 	</tbody>
</table>

<h3>Response Status</h3>
If data successfully posted, below is the response status
  <pre class="prettyprint">
{
  "status": {
    "error": false,
    "code": 201,
    "message": "'.$service_name.' created successfully",
    "id": "23"
  }
}
</pre>
If you get the following error, the problem is you forgot the header \'Authentication\' token
<pre class="prettyprint">
	{
  "status": {
    "error": true,
    "code": 400,
    "message": "Api key is misssing"
  }
}
</pre>
If the errors like this one, then your token was wrong or expired. So you have to get a new one. Just do post request to login url.
<pre class="prettyprint">
	{
  "status": {
    "error": true,
    "code": 401,
    "message": "Access Denied. Invalid Api key"
  }
}
</pre>
</section>


<section id=\'update\'>
  <h2 class=\'page-header\'><a href="#update">PUT /api/'.$service_name.'/update/:id</a></h2>
  <p class=\'lead\'>
    Use PUT method to Update '.$service_name.' data. The request parameter structure is just like previous the <a href=\'#post_user\'>post data</a> <br>
  </p>
<h3>Resource URL</h3>
<h4><?=base_url();?>api/'.$service_name.'/update/:id</h4>
<h3>Sample URL</h3>
<h4><?=base_url();?>api/'.$service_name.'/update/107</h4>
This uri will request to update record with primary key 107
<h3>Response Status</h3>
If data successfully posted, below is the response status
  <pre class="prettyprint">
{
  "status": {
    "error": false,
    "code": 200,
    "message": "'.$service_name.' Updated successfully"
  }
}
</pre>
</section>

<section id=\'delete\'>
  <h2 class=\'page-header\'><a href="#delete">DELETE /api/'.$service_name.'/delete/:id</a></h2>
  <p class=\'lead\'>
    Delete '.$service_name.' Data <br>
  </p>
<h3>Resource URL</h3>
<h4><?=base_url();?>api/'.$service_name.'/delete/:id</h4>

Add header request with token value if you previously enable the authentication, otherwise just send the request.
<h3>Example Result</h3>
<h4><?=base_url();?>api/'.$service_name.'/delete/107</h4>
It will request delete for the record with id 107
<h3>Response Status</h3>
If data successfully posted, below is the response status
  <pre class="prettyprint">
{
  "status": {
    "error": false,
    "code": 200,
    "message": "'.$service_name.' Deleted successfully"
  }
}
</pre>
</section>


        </div><!-- /.content -->
      </div><!-- /.content-wrapper -->

      <footer class="main-footer">
        <div class="pull-right hidden-xs">
          <b>Version</b> 2.0
        </div>
        <strong>Copyright &copy; 2014-2015 <a href="http://almsaeedstudio.com">Almsaeed Studio</a>.</strong> All rights reserved.
      </footer>

    </div><!-- ./wrapper -->

    <!-- jQuery 2.1.3 -->
    <script src="../../admina/assets/plugins/jQuery/jQuery-2.1.3.min.js"></script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="../../admina/assets/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <!-- FastClick -->
    <script src=\'../../admina/assets/plugins/fastclick/fastclick.min.js\'></script>
    <!-- AdminLTE App -->
    <script src="../../admina/assets/dist/js/app.min.js" type="text/javascript"></script>
    <!-- SlimScroll 1.3.0 -->
    <script src="../../admina/assets/plugins/slimScroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <script src="../../admina/assets/plugins/google-code-prettify/run_prettify.js"></script>
    <script>
/*
 * Documentation specific JS script
 */
$(function () {
  var slideToTop = $("<div />");
  slideToTop.html(\'<i class="fa fa-chevron-up"></i>\');
  slideToTop.css({
    position: \'fixed\',
    bottom: \'20px\',
    right: \'25px\',
    width: \'40px\',
    height: \'40px\',
    color: \'#eee\',
    \'font-size\': \'\',
    \'line-height\': \'40px\',
    \'text-align\': \'center\',
    \'background-color\': \'#222d32\',
    cursor: \'pointer\',
    \'border-radius\': \'5px\',
    \'z-index\': \'99999\',
    opacity: \'.7\',
    \'display\': \'none\'
  });
  slideToTop.on(\'mouseenter\', function () {
    $(this).css(\'opacity\', \'1\');
  });
  slideToTop.on(\'mouseout\', function () {
    $(this).css(\'opacity\', \'.7\');
  });
  $(\'.wrapper\').append(slideToTop);
  $(window).scroll(function () {
    if ($(window).scrollTop() >= 150) {
      if (!$(slideToTop).is(\':visible\')) {
        $(slideToTop).fadeIn(500);
      }
    } else {
      $(slideToTop).fadeOut(500);
    }
  });
  $(slideToTop).click(function () {
    $("body").animate({
      scrollTop: 0
    }, 500);
  });
  $(".sidebar-menu li a").click(function () {
    var $this = $(this);
    var target = $this.attr("href");
    if (typeof target === \'string\') {
      $("body").animate({
        scrollTop: ($(target).offset().top) + "px"
      }, 500);
    }
  });
});
    </script>
  </body>
</html>
';