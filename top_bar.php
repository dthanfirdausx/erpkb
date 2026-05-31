<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<header class="main-header">

        <!-- Logo -->
        <a href="<?=base_index();?>" class="logo"><b><?= shortTittle ?></b></a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
          <!-- Sidebar toggle button-->
           <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
          </a>
          <!-- Navbar Right Menu -->

          <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">

             <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" 
       href="#"  
       role="button" 
       data-bs-toggle="dropdown">

        <?php $language = $_SESSION['lang'] ?? 'id'; ?>

        <?php if($language == 'id'): ?>
            <img src="https://flagcdn.com/w20/id.png"> Indonesia
        <?php elseif($language == 'en'): ?>
            <img src="https://flagcdn.com/w20/us.png"> English
        <?php elseif($language == 'ko'): ?>
            <img src="https://flagcdn.com/w20/kr.png"> 한국어
        <?php elseif($language == 'zh'): ?>
            <img src="https://flagcdn.com/w20/cn.png"> 中文
        <?php endif; ?>

    </a>

    <ul class="dropdown-menu dropdown-menu-end">

        <li class="<?= ($language=='id') ? 'active' : '' ?>">
            <a class="dropdown-item" href="#" onclick="changeLanguage('id')">
                <img src="https://flagcdn.com/w20/id.png">
                Indonesia
            </a>
        </li>

        <li class="<?= ($language=='en') ? 'active' : '' ?>">
            <a class="dropdown-item" href="#" onclick="changeLanguage('en')">
                <img src="https://flagcdn.com/w20/us.png">
                English
            </a>
        </li>

        <li class="<?= ($language=='ko') ? 'active' : '' ?>">
            <a class="dropdown-item" href="#" onclick="changeLanguage('ko')">
                <img src="https://flagcdn.com/w20/kr.png">
                한국어 (Korean)
            </a>
        </li>

        <li class="<?= ($language=='zh') ? 'active' : '' ?>">
            <a class="dropdown-item" href="#" onclick="changeLanguage('zh')">
                <img src="https://flagcdn.com/w20/cn.png">
                中文 (Chinese)
            </a>
        </li>

    </ul>
</li>
              
            <!--   Messages: style can be found in dropdown.less
            <li class="dropdown messages-menu">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-envelope-o"></i>
                <span class="label label-success">4</span>
              </a>
              <ul class="dropdown-menu">
                <li class="header">You have 4 messages</li>
                <li>
                  inner menu: contains the actual data
                  <ul class="menu">
                    <li>start message
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user2-160x160.jpg" class="img-circle" alt="User Image"/>
                        </div>
                        <h4>
                          Support Team
                          <small><i class="fa fa-clock-o"></i> 5 mins</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>end message
                    <li>
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user3-128x128.jpg" class="img-circle" alt="user image"/>
                        </div>
                        <h4>
                          AdminLTE Design Team
                          <small><i class="fa fa-clock-o"></i> 2 hours</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user4-128x128.jpg" class="img-circle" alt="user image"/>
                        </div>
                        <h4>
                          Developers
                          <small><i class="fa fa-clock-o"></i> Today</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user3-128x128.jpg" class="img-circle" alt="user image"/>
                        </div>
                        <h4>
                          Sales Department
                          <small><i class="fa fa-clock-o"></i> Yesterday</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user4-128x128.jpg" class="img-circle" alt="user image"/>
                        </div>
                        <h4>
                          Reviewers
                          <small><i class="fa fa-clock-o"></i> 2 days</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="footer"><a href="#">See All Messages</a></li>
              </ul>
            </li>
            Notifications: style can be found in dropdown.less
            <li class="dropdown notifications-menu">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-bell-o"></i>
                <span class="label label-warning">10</span>
              </a>
              <ul class="dropdown-menu">
                <li class="header">You have 10 notifications</li>
                <li>
                  inner menu: contains the actual data
                  <ul class="menu">
                    <li>
                      <a href="#">
                        <i class="fa fa-users text-aqua"></i> 5 new members joined today
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <i class="fa fa-warning text-yellow"></i> Very long description here that may not fit into the page and may cause design problems
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <i class="fa fa-users text-red"></i> 5 new members joined
                      </a>
                    </li>

                    <li>
                      <a href="#">
                        <i class="fa fa-shopping-cart text-green"></i> 25 sales made
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <i class="fa fa-user text-red"></i> You changed your username
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="footer"><a href="#">View all</a></li>
              </ul>
            </li>
            Tasks: style can be found in dropdown.less
            <li class="dropdown tasks-menu">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-flag-o"></i>
                <span class="label label-danger">9</span>
              </a>
              <ul class="dropdown-menu">
                <li class="header">You have 9 tasks</li>
                <li>
                  inner menu: contains the actual data
                  <ul class="menu">
                    <li>Task item
                      <a href="#">
                        <h3>
                          Design some buttons
                          <small class="pull-right">20%</small>
                        </h3>
                        <div class="progress xs">
                          <div class="progress-bar progress-bar-aqua" style="width: 20%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <span class="sr-only">20% Complete</span>
                          </div>
                        </div>
                      </a>
                    </li>end task item
                    <li>Task item
                      <a href="#">
                        <h3>
                          Create a nice theme
                          <small class="pull-right">40%</small>
                        </h3>
                        <div class="progress xs">
                          <div class="progress-bar progress-bar-green" style="width: 40%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <span class="sr-only">40% Complete</span>
                          </div>
                        </div>
                      </a>
                    </li>end task item
                    <li>Task item
                      <a href="#">
                        <h3>
                          Some task I need to do
                          <small class="pull-right">60%</small>
                        </h3>
                        <div class="progress xs">
                          <div class="progress-bar progress-bar-red" style="width: 60%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <span class="sr-only">60% Complete</span>
                          </div>
                        </div>
                      </a>
                    </li>end task item
                    <li>Task item
                      <a href="#">
                        <h3>
                          Make beautiful transitions
                          <small class="pull-right">80%</small>
                        </h3>
                        <div class="progress xs">
                          <div class="progress-bar progress-bar-yellow" style="width: 80%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <span class="sr-only">80% Complete</span>
                          </div>
                        </div>
                      </a>
                    </li>end task item
                  </ul>
                </li>
                <li class="footer">
                  <a href="#">View all tasks</a>
                </li>
              </ul>
            </li> -->
              <!-- User Account: style can be found in dropdown.less -->
            
              <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                  <img src="<?=base_url();?>upload/back_profil_foto/<?=$db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->foto_user?>" class="user-image" alt="User Image"/>
                  <span class="hidden-xs"><?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->first_name)?> <?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->last_name);?></span>
                </a>

                <ul class="dropdown-menu">
                  <!-- User image -->
                  <li class="user-header">
                    <img src="<?=base_url();?>upload/back_profil_foto/<?=$db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->foto_user?>" class="img-circle" alt="User Image" />
                    <p>
              <?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->first_name)?> <?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->last_name);?> - <?=$db->fetch_single_row('sys_group_users','level',$_SESSION['group_level'])->deskripsi?>
            <small>Member since <?=$db->fetch_custom_single("SELECT MONTHNAME(STR_TO_DATE(month(date_created), '%m')) as bulan from sys_users where id=? ",array('id'=>$_SESSION['id_user']))->bulan;?> <?=$db->fetch_custom_single("select year(date_created) as tahun from sys_users where id=?",array('id'=>$_SESSION['id_user']))->tahun;?> </small>
                                    </p>
                  </li>
                  <!-- Menu Body -->
     <!--              <li class="user-body">
                    <div class="col-xs-4 text-center">
                      <a href="#">Followers</a>
                    </div>
                    <div class="col-xs-4 text-center">
                      <a href="#">Sales</a>
                    </div>
                    <div class="col-xs-4 text-center">
                      <a href="#">Friends</a>
                    </div>
                  </li> -->
                  <!-- Menu Footer-->
                   <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="<?=base_index();?>profil" class="btn btn-default btn-flat">Profile</a>
                                    </div>
                                    <div class="pull-right">
                                        <a href="<?=base_admin();?>logout.php" class="btn btn-default btn-flat">Sign out</a>
                                    </div>
                                </li>


                </ul>
              </li>
            </ul>
          </div>
        </nav>
      </header>

<div class="modal modal-danger" id="ucing" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog"> <div class="modal-content"><div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> <h4 class="modal-title"><?php echo $lang['confirm'];?></h4> </div> <div class="modal-body"> <p> <i class="fa fa-info-circle fa-2x" style=" vertical-align: middle;margin-right:5px"></i> <span> <?php echo $lang['delete_confirm'];?></span></p> </div> <div class="modal-footer"> <button type="button" id="delete" class="btn btn-danger">Delete</button> <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div><!-- /.modal -->
<div class="modal modal-warning" id="informasi" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog"> <div class="modal-content"><div class="modal-header"> <h4 class="modal-title"><?=$lang['info'];?></h4> </div> <div class="modal-body"> <p id="isi_informasi">
<?=$lang['session_over'];?>
</p> </div> <div class="modal-footer"> <a href="<?=base_admin();?>" class="btn btn-outline pull-left"><?=$lang['relogin'];?></a> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div><!-- /.modal -->
