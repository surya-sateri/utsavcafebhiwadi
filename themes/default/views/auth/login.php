<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?><!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title><?= $title ?></title>
        <script type="text/javascript">if (parent.frames.length !== 0) {
                top.location = '<?= site_url('pos') ?>';
            }</script>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="<?= $assets ?>images/icon.png"/>
        <link href="<?= $assets ?>styles/theme.css" rel="stylesheet"/>
        <link href="<?= $assets ?>styles/style.css" rel="stylesheet"/>
        <link href="<?= $assets ?>styles/helpers/login.css" rel="stylesheet"/>
        <script type="text/javascript" src="<?= $assets ?>js/jquery-2.0.3.min.js"></script>
        <!--[if lt IE 9]>
        <script src="<?= $assets ?>js/respond.min.js"></script>
        <![endif]-->
        <script>
            function viewPassword() {
                var PwdType = $("#Password").attr("type");
                if (PwdType == 'password')
                    $("#Password").prop("type", "text");
                else
                    $("#Password").prop("type", "password");
            }
        </script>
        <style>
            .login-page .input-group .input-group-addon{cursor:pointer;}
        </style>
<?php $logopath = base_url("assets/icons/")?>
        <link rel="apple-touch-icon" sizes="57x57" href="<?=$logopath?>apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="<?= $logopath?>apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="<?= $logopath?>apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="<?= $logopath?>apple-icon-76x76.png">        
        <link rel="icon" type="image/png" sizes="192x192"  href="<?= $logopath?>android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $logopath?>favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="<?= $logopath?>favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= $logopath?>favicon-16x16.png">
        <link rel="manifest" href="<?= $logopath?>manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="<?= $logopath?>ms-icon-144x144.png">
        <meta name="theme-color" content="#ffffff">
    </head>

    <body class="login-page">
        <noscript>
        <div class="global-site-notice noscript">
            <div class="notice-inner">
                <p>
                    <strong>JavaScript seems to be disabled in your browser.</strong><br>You must have JavaScript enabled in
                    your browser to utilize the functionality of this website.
                </p>
            </div>
        </div>
        </noscript>
        <div class="page-back">
            <div class="text-center">
                <?php
                if ($Settings->logo2) {
                    echo '<img src="' . base_url('assets/uploads/logos/' . $Settings->logo2) . '" alt="' . $Settings->site_name . '" style="margin-bottom:10px;" />';
                }
                ?>
            </div>

            <div id="login">
                <div class=" container">

                    <div class="login-form-div">
                        <div class="login-content">
                                <?php if ($Settings->mmode) { ?>
                                <div class="alert alert-warning">
                                    <button data-dismiss="alert" class="close" type="button">×</button>
                                <?= lang('site_is_offline') ?>
                                </div>
                                <?php
                            }
                            if ($error) {
                                ?>
                                <div class="alert alert-danger">
                                    <button data-dismiss="alert" class="close" type="button">×</button>
                                    <ul class="list-group"><?= $error; ?></ul>
                                </div>
                                <?php
                            }
                            if ($message) {
                                ?>
                                <div class="alert alert-success">
                                    <button data-dismiss="alert" class="close" type="button">×</button>
                                    <ul class="list-group"><?= $message; ?></ul>
                                </div>
                                <?php
                            }
                            ?>
<?php echo form_open("auth/login", 'class="login"'); ?>
                            <div class="div-title col-sm-12">
                                <h3 class="text-primary"><?= lang('login_to_your_account') ?></h3>
                            </div>
                            <div class="col-sm-12">
                                <div class="textbox-wrap form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                        <input type="text" value="" required="required" class="form-control" name="identity"
                                               placeholder="<?= lang('username') ?> / Email / Mobile"/>
                                    </div>
                                </div>
                                <div class="textbox-wrap form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                        <input type="password" value="" id="Password" required="required" class="form-control " name="password"
                                               placeholder="<?= lang('pw') ?>"/>
                                        <div class="input-group-addon" >
                                            <i class="fa fa-eye" onclick="return viewPassword();"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            if ($Settings->captcha) {
                                ?>
                                <div class="col-sm-12">
                                    <div class="textbox-wrap form-group">
                                        <div class="row">
                                            <div class="col-sm-6 div-captcha-left">
                                                <span class="captcha-image"><?php echo $image; ?></span>
                                            </div>
                                            <div class="col-sm-6 div-captcha-right">
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <a href="<?= base_url(); ?>auth/reload_captcha" class="reload-captcha">
                                                            <i class="fa fa-refresh"></i>
                                                        </a>
                                                    </span>
    <?php echo form_input($captcha); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            } /* echo $recaptcha_html; */
                            ?>

                            <div class="form-action col-sm-12">
                                <div class="checkbox pull-left">
                                    <div class="custom-checkbox">
<?php echo form_checkbox('remember', '1', FALSE, 'id="remember"'); ?>
                                    </div>
                                    <span class="checkbox-text pull-left"><label for="remember"><?= lang('remember_me') ?></label></span>
                                </div>
                                <input type="hidden" name="login_device" id="login_device" />
                                <button type="submit" class="btn btn-success pull-right"><?= lang('login') ?> &nbsp; <i class="fa fa-sign-in"></i></button>
                            </div>
<?php echo form_close(); ?>
                            <div class="clearfix"></div>
                        </div>
                        <div class="login-form-links link2">
                            <h4 class="text-danger"><?= lang('forgot_your_password') ?></h4>
                            <span><?= lang('dont_worry') ?></span>
                            <a href="#forgot_password" class="text-danger forgot_password_link"><?= lang('click_here') ?></a>
                            <span><?= lang('to_rest') ?></span>
                        </div>
                        <?php
                        if ($Settings->allow_reg) {
                            ?>
                            <div class="login-form-links link1">
                                <h4 class="text-info"><?= lang('dont_have_account') ?></h4>
                                <span><?= lang('no_worry') ?></span>
                                <a href="#register" class="text-info register_link"><?= lang('click_here') ?></a>
                                <span><?= lang('to_register') ?></span>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div id="forgot_password" style="display: none;">
                <div class=" container">
                    <div class="login-form-div">
                        <div class="login-content">
                            <?php
                            if ($error) {
                                ?>
                                <div class="alert alert-danger">
                                    <button data-dismiss="alert" class="close" type="button">×</button>
                                    <ul class="list-group"><?= $error; ?></ul>
                                </div>
                                <?php
                            }
                            if ($message) {
                                ?>
                                <div class="alert alert-success">
                                    <button data-dismiss="alert" class="close" type="button">×</button>
                                    <ul class="list-group"><?= $message; ?></ul>
                                </div>
                                <?php
                            }
                            ?>
                            <div class="div-title col-sm-12">
                                <h3 class="text-primary"><?= lang('forgot_password') ?></h3>
                            </div>
                                    <?php echo form_open("auth/forgot_password", 'class="login" data-toggle="validator"'); ?>
                            <div class="col-sm-12">
                                <p>
<?= lang('type_email_to_reset'); ?>
                                </p>
                                <div class="textbox-wrap form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-envelope"></i></span>
                                        <input type="email" name="forgot_email" class="form-control "
                                               placeholder="<?= lang('email_address') ?>" required="required"/>
                                    </div>
                                </div>
                                <div class="form-action">
                                    <a class="btn btn-success pull-left login_link" href="#login">
                                        <i class="fa fa-chevron-left"></i> <?= lang('back') ?>
                                    </a>
                                    <button type="submit" class="btn btn-primary pull-right">
<?= lang('submit') ?> &nbsp;&nbsp; <i class="fa fa-envelope"></i>
                                    </button>
                                </div>
                            </div>
<?php echo form_close(); ?>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            if ($Settings->allow_reg) {
                ?>
                <div id="register">
                    <div class="container">
                        <div class="registration-form-div reg-content">
    <?php echo form_open("auth/register", 'class="login" data-toggle="validator"'); ?>
                            <div class="div-title col-sm-12">
                                <h3 class="text-primary"><?= lang('register_account_heading') ?></h3>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
    <?= lang('first_name', 'first_name'); ?>
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-user"></i></span>
                                        <input type="text" name="first_name" class="form-control " placeholder="<?= lang('first_name') ?>" required="required"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
    <?= lang('last_name', 'last_name'); ?>
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-user"></i></span>
                                        <input type="text" name="last_name" class="form-control " placeholder="<?= lang('last_name') ?>" required="required"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
    <?= lang('company', 'company'); ?>
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-building"></i></span>
                                        <input type="text" name="company" class="form-control " placeholder="<?= lang('company') ?>"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
    <?= lang('phone', 'phone'); ?>
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-phone-square"></i></span>
                                        <input type="text" name="phone" class="form-control " placeholder="<?= lang('phone') ?>" required="required"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
    <?= lang('username', 'username'); ?>
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-user"></i></span>
                                        <input type="text" name="username" class="form-control " placeholder="<?= lang('username') ?>" required="required"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
    <?= lang('email', 'email'); ?>
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-envelope"></i></span>
                                        <input type="email" name="email" class="form-control " placeholder="<?= lang('email_address') ?>" required="required"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                        <?php echo lang('password', 'password1'); ?>
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-key"></i></span>
    <?php echo form_password('password', '', 'class="form-control tip" id="password1" required="required" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" data-bv-regexp-message="' . lang('pasword_hint') . '"'); ?>
                                    </div>
                                    <span class="help-block"><?= lang('pasword_hint') ?></span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                        <?php echo lang('confirm_password', 'confirm_password'); ?>
                                    <div class="input-group">
                                        <span class="input-group-addon "><i class="fa fa-key"></i></span>
    <?php echo form_password('confirm_password', '', 'class="form-control" id="confirm_password" required="required" data-bv-identical="true" data-bv-identical-field="password" data-bv-identical-message="' . lang('pw_not_same') . '"'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12">
                                <a href="#login" class="btn btn-success pull-left login_link">
                                    <i class="fa fa-chevron-left"></i> <?= lang('back') ?>
                                </a>
                                <button type="submit" class="btn btn-primary pull-right">
    <?= lang('register_now') ?> <i class="fa fa-user"></i>
                                </button>
                            </div>

    <?php echo form_close(); ?>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <script src="<?= $assets ?>js/jquery.js"></script>
        <script src="<?= $assets ?>js/bootstrap.min.js"></script>
        <script src="<?= $assets ?>js/jquery.cookie.js"></script>
        <script src="<?= $assets ?>js/login.js"></script>
        <script type="text/javascript">
                $(document).ready(function () {
                    localStorage.clear();
                    var hash = window.location.hash;
                    if (hash && hash != '') {
                        $("#login").hide();
                        $(hash).show();
                    }
                });


                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i.test(navigator.userAgent)) {
                    $('#login_device').val('mobile');
                } else {
                    $('#login_device').val('computer');
                }

        </script>

    </body>
</html>
