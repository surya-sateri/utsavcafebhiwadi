<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
     .select2-container-multi{height: auto !important;}
</style> 
<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-users"></i><?= lang('create_user'); ?></h2>
    </div>
<p class="introtext"><?php echo lang('create_user'); ?></p>
    <div class="box-content">
        <div class="row">
            <div class="col-lg-12">
                

                <?php $attrib = array('class' => 'form-horizontal', 'data-toggle' => 'validator', 'role' => 'form');
                echo form_open("auth/create_user", $attrib);
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-5">
                            <div class="form-group">
                                <?php echo lang('first_name', 'first_name'); ?>
                                <div class="controls">
                                    <?php
                                    $first_name = isset($_POST['first_name'])?$_POST['first_name']:'';
                                    echo form_input('first_name', $first_name, 'class="form-control" id="first_name" onkeypress="return onlyAlphabets1(event,this);" type="text" required="required" '); ?><!--pattern=".{3,10}"-->
                                    <span id="error2" style="color:#a94442;font-size:10px; display: none">please enter alphabets only</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <?php echo lang('last_name', 'last_name'); ?>
                                <div class="controls">
                                    <?php 
                                    $last_name = isset($_POST['last_name'])?$_POST['last_name']:'';
                                    echo form_input('last_name', $last_name, 'class="form-control" id="last_name" onkeypress="return onlyAlphabets1(event,this);" type="text" required="required"'); ?>
                                   <span id="error2" style="color:#a94442;font-size:10px; display: none">please enter alphabets only</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <?= lang('gender', 'gender'); ?>
                                <?php
                                $ge[''] = array('male' => lang('male'), 'female' => lang('female'));
                                echo form_dropdown('gender', $ge, (isset($_POST['gender']) ? $_POST['gender'] : ''), 'class="tip form-control" id="gender" data-placeholder="' . lang("select") . ' ' . lang("gender") . '" required="required"');
                                ?>
                            </div>

                            <div class="form-group">
                                <?php echo lang('company', 'company'); ?>
                                <div class="controls">
                                    <?php 
                                     $company = isset($_POST['company'])?$_POST['company']:'';
                                    echo form_input('company', $company, 'class="form-control" id="company" required="required"'); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <?php echo lang('phone', 'phone'); ?>
                                <div class="controls">
                                    <?php 
                                    $phone = isset($_POST['phone'])?$_POST['phone']:'';
                                    echo form_input('phone', $phone, 'class="form-control" data-bv-phone="true" data-bv-phone-country="US" maxlength="10"  id="phone" required="required"onkeypress="return IsNumeric(event)" ondrop="return 
                                         false" onpaste="return false"'); ?>
                                       <span id="error" style="color:#a94442; display: none;font-size:11px;">Please Enter numbers only</span>

                                </div>
                            </div>

                            <div class="form-group">
                                <?php echo lang('email', 'email'); ?>
                                <div class="controls">
                                    <?php 
                                     $email = isset($_POST['email'])?$_POST['email']:'';
                                    ?>
                                    <input type="email" value="<?php echo $email;?>" id="email" name="email" class="form-control"
                                           required="required"/>
                                    <?php /* echo form_input('email', '', 'class="form-control" id="email" required="required"'); */ ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <?php echo lang('username', 'username'); ?>
                                <div class="controls">
                                    <input type="text" id="username" name="username" class="form-control"
                                           required="required" pattern=".{4,20}"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <?php echo lang('password', 'password'); ?>
                                <div class="controls">
                                    <?php echo form_password('password', '', 'class="form-control tip" id="password" required="required" pattern="^(?=.*?[A-Z])(?=(.*[a-z]){1,})(?=(.*[\d]){1,})(?=(.*[\W]){1,})(?!.*\s).{8,}$"  data-bv-regexp-message="'.lang('pasword_hint').'"'); ?>
                                    <span class="help-block"><?= lang('pasword_hint') ?></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <?php echo lang('confirm_password', 'confirm_password'); ?>
                                <div class="controls">
                                    <?php echo form_password('confirm_password', '', 'class="form-control" id="confirm_password" required="required" data-bv-identical="true" data-bv-identical-field="password" data-bv-identical-message="' . lang('pw_not_same') . '"'); ?>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-5 col-md-offset-1">

                            <div class="form-group">
                                <?= lang('status', 'status'); ?>
                                <?php
                                $opt = array(1 => lang('active'), 0 => lang('inactive'));
                                echo form_dropdown('status', $opt, (isset($_POST['status']) ? $_POST['status'] : ''), 'id="status" required="required" class="form-control select" style="width:100%;"');
                                ?>
                            </div>
                            
                            <div class="form-group">
                                <?= lang("group", "group"); ?>
                                <?php
                                foreach ($groups as $group) {
                                    if ($group['name'] != 'customer' && $group['name'] != 'supplier') {
                                        $gp[$group['id']] = $group['name'];
                                    }
                                }
                                echo form_dropdown('group', $gp, (isset($_POST['group']) ? $_POST['group'] : ''), 'id="group" required="required" class="form-control select" style="width:100%;"');
                                ?>
                            </div>

                            <div class="clearfix"></div>
                            <div class="no">
                                <div class="form-group">
                                    <?= lang("biller", "biller"); ?>
                                    <?php
                                    $bl[""] = lang('select').' '.lang('biller');
                                    foreach ($billers as $biller) {
                                        $bl[$biller->id] = $biller->company != '-' ? $biller->company : $biller->name;
                                    }
                                    echo form_dropdown('biller', $bl, (isset($_POST['biller']) ? $_POST['biller'] : ''), 'id="biller" class="form-control select" style="width:100%;"');
                                    ?>
                                </div>

                                <div class="form-group">
                                    <?= lang("warehouse", "warehouse"); ?>
                                    <?php
                                   // $wh[''] = lang('select').' '.lang('warehouse');
                                    foreach ($warehouses as $warehouse) {
                                        $wh[$warehouse->id] = $warehouse->name;
                                    }
                                    echo form_dropdown('warehouse[]', $wh, (isset($_POST['warehouse']) ? $_POST['warehouse'] : ''), 'id="warehouse" multiple class="form-control select" style="width:100%;" ');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?= lang('offline_mobile_app_access', 'offline_mobile_app_access'); ?>
                                    <?php
                                    $opt_mbaccess = array(1 => lang('active'), 0 => lang('inactive'));
                                    echo form_dropdown('offline_mobile_app_access', $opt_mbaccess, (isset($_POST['offline_mobile_app_access']) ? $_POST['offline_mobile_app_access'] : ''), 'id="offline_mobile_app_access" class="form-control select" style="width:100%;"');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?= lang('offline_windows_app_access', 'offline_windows_app_access'); ?>
                                    <?php
                                    //$opt_winacces = array(1 => lang('active'), 0 => lang('inactive'));
                                    $opt_winacces = array(1 => lang('active'));
                                    echo form_dropdown('offline_windows_app_access', $opt_winacces, (isset($_POST['offline_windows_app_access']) ? $_POST['offline_windows_app_access'] : ''), 'id="offline_windows_app_access" class="form-control select" style="width:100%;"');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?= lang("view_right", "view_right"); ?>
                                    <?php
                                    $vropts = array(1 => lang('all_records'), 0 => lang('own_records'));
                                    echo form_dropdown('view_right', $vropts, (isset($_POST['view_right']) ? $_POST['view_right'] : 1), 'id="view_right" class="form-control select" style="width:100%;"');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?= lang("edit_right", "edit_right"); ?>
                                    <?php
                                    $opts = array(1 => lang('yes'), 0 => lang('no'));
                                    echo form_dropdown('edit_right', $opts, (isset($_POST['edit_right']) ? $_POST['edit_right'] : 0), 'id="edit_right" class="form-control select" style="width:100%;"');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?= lang("allow_discount", "allow_discount"); ?>
                                    <?= form_dropdown('allow_discount', $opts, (isset($_POST['allow_discount']) ? $_POST['allow_discount'] : 0), 'id="allow_discount" class="form-control select" style="width:100%;"'); ?>
                                </div>
                 
                            </div>
                              <?php if($pos_type == 'restaurant'){ ?>
                                <div class="form-group">
                                    <label for="restaurantTables"> Tables</label>
                                    <select class="form-control" name="table_assign[]" multiple="true">
                                            <?php foreach($restaurantTables as $tables){ ?>
                                                <option value="<?= $tables->id ?>"><?= $tables->name ?></option>
                                            <?php } ?>
                                    </select>    
                                </div>    
                            <?php } ?>
                            <div class="row">
                                <div class="col-md-8">
                                    <label class="checkbox" for="notify">
                                        <input type="checkbox" name="notify" value="1" id="notify" checked="checked"/>
                                        <?= lang('notify_user_by_email') ?>
                                    </label>
                                </div>
                                <div class="clearfix"></div>
                            </div>

                        </div>
                    </div>
                </div>

                <p><?php echo form_submit('add_user', lang('add_user'), 'class="btn btn-primary"'); ?></p>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" charset="utf-8">
    $(document).ready(function () {
       // $('#username').disableAutoFill();
       // $('#password').disableAutoFill();
    
        $('.no').slideUp();
        $('#group').change(function (event) {
            var group = $(this).val();
            if (group == 1 || group == 2) {
                $('.no').slideUp();
            } else {
                $('.no').slideDown();
            }
        });
    });
    var specialKeys = new Array();
	specialKeys.push(8); //Backspace
function IsNumeric(e) {
            var keyCode = e.which ? e.which : e.keyCode
            var ret = ((keyCode >= 48 && keyCode <= 57) || specialKeys.indexOf(keyCode) != -1);
            document.getElementById("error").style.display = ret ? "none" : "inline";
                            return ret;
}

function onlyAlphabets1(e, t) {
        var charCode = e.which ? e.which : e.keyCode
        var ret= (charCode == 32 || (charCode>=97 && charCode<=122)|| (charCode>=65 && charCode<=90));
        //document.getElementById("error2").style.display = ret ? "none" : "inline";
	return ret;	
} 
</script>
