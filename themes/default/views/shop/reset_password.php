<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>E-shop: Reset Password</title>
        <link href="<?= $assets?>T1/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?= $assets?>T1/css/font-awesome.min.css" rel="stylesheet">
        <link href="<?= $assets?>T1/css/animate.css" rel="stylesheet">
        <link href="<?= $assets?>T1/css/main.css" rel="stylesheet">
        <link href="<?= $assets?>T1/css/responsive.css" rel="stylesheet">	
        <link href="<?= $assets?>T1/css/hover.css" rel="stylesheet" media="all">
    </head><!--/head-->
    <body>
        <header id="header"><!--header--> 
            <div class="header-middle"><!--header-middle-->
                <div class="container">
                     
                </div>
            </div><!--/header-middle-->
             
        </header><!--/header-->
        
        <section class="middle_section"><!--Middle section view-->
            <div class="container">
                <div class="row">                    
                    <div class="container wrapper login-page">
                    <div class="login-bg"></div>
                    <div class="col-md-4 col-md-offset-4  col-xs-12">
                        <p><span class="login-logo1" ><img src="<?= $baseurl;?>assets/uploads/logos/<?= $this->Settings->logo?>" alt="Logo" class="img-responsivee" /></span></p>
                        <input type="hidden" id="baseurl" value="<?= $baseurl;?>" />
                        <div class="login-form"><!--login form-->
                            <h2>Reset Password</h2>            
                            <p class="text-danger bg-danger"><?php echo $login_error; ?></p>
                            <p class="text-success">Confirm your identity with your email/mobile plus a six-digit verification code. Code has been sent on your registered email and mobile.</p>
                            <?php echo form_open('shop/reset_password');?>
                            <input type="hidden" id="identity" name="identity" required="required" class="form-control" maxlength="30" value="<?=($_POST['identity'])?$_POST['identity'] :$customerIdentity?>" placeholder="Email or Mobile Number" />
                      
                                <input type="text" id="verification_code" name="verification_code" required="required" class="form-control" value="<?=$_POST['verification_code']?>" maxlength="6" placeholder="Verification Code" />
                                <input type="password" id="new_passwd" name="new_passwd" required="required" class="form-control" value="<?=$_POST['new_passwd']?>" maxlength="15" placeholder="New Password" />
                                <input type="password" id="confirm_passwd" name="confirm_passwd" required="required" class="form-control" value="<?=$_POST['confirm_passwd']?>" maxlength="15" placeholder="Confirm Password" />
                                
                                <button type="submit" name="btn_submit" value="resetpasswd" class="btn btn-lg">Reset Password</button><br/>
                              
                                <?php echo form_close();?>
                                
                                <div class="row">                                    
                                    <div class="sign-in"><a href="<?= base_url('shop/login');?>" class="btn btn-lg" >Login! </a></div>
                              
                                    <div class="sign-up"><a href="<?= base_url('shop/signup');?>" class="btn btn-lg" >New User Signup! </a></div> 

                                </div> 
                            </div>
                        </div><!--/login form-->
                    </div>
                </div>
        </div>
    </div>
</section><!--/Middle section view -->
<script src="<?= $assets?>T1/js/jquery.js"></script>
<script src="<?= $assets?>T1/js/bootstrap.min.js"></script>

    </body>
</html>