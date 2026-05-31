

                <!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                     Import Page
                    </h1>
                           <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>aktivitas-kuliah-mahasiswa">Import Page</a></li>
                        <li class="active">Import Page</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
<div class="row">
    <div class="col-lg-12">
        <div class="box box-solid box-primary">
                                 <div class="box-header">
                                    <h3 class="box-title">Import Page</h3>
                                    <div class="box-tools pull-right">
                                        <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                        <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                    </div>
                                </div>

                  <div class="box-body">
 <div class="alert alert-danger" id="error_msg_imports" style="display: none;">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <strong class="error_msg_import"></strong> 
        </div>
                     <form id="input_import" method="post" class="form-horizontal" action="<?=base_admin();?>system/page/page_action.php?act=import">
                      <div class="form-group">
                        <label for="Upload Data" class="control-label col-lg-2">Import Page</label>
                        <div class="col-lg-10">
                          <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="input-group">
                              <div class="form-control uneditable-input span3" data-trigger="fileinput">
                                <i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span>
                              </div>
                              <span class="input-group-addon btn btn-default btn-file"><span class="fileinput-new">Select file</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="page_file" required>
                              </span>
                              <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                        </div>
                        <input type="hidden" id="uri" value="<?=base_admin();?>">
                      </div><!-- /.form-group -->
                      <div class="form-group">
                        <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                        <div class="col-lg-10">
                          <input type="submit" class="btn btn-primary btn-flat" value="Upload Data">
                        </div>
                      </div><!-- /.form-group -->
                    </form>
 <a onclick="window.history.back(-1)" class="btn btn-success btn-flat"><i class="fa fa-step-backward"></i> Back</a>

                  </div>
                  </div>
              </div>
</div>

                </section><!-- /.content -->
