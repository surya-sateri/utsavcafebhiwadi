<?php defined('BASEPATH') OR exit('No direct script access allowed');
$sms_text_limit = $this->sma->smsCharLimit();
  $action = site_url('smsdashboard/templateEdit').'?template_id='.$template->id;
    $attrib = array('data-toggle' => 'validator', 'role' => 'form', 'id' => 'edit-template-form');
    echo form_open_multipart($action, $attrib);  
    
?> 
<input type="hidden" name="edit_template" value="1"> 
<div class="row">
    <div class="col-md-12">
        <div class="form-group" id="form_loader"></div>
    </div>
    <div class="col-md-12" id="form_element">
     <div class="col-md-12">
        <div class="form-group">
            <label for="product_details">Name/Subject</label>
            <?php echo form_input('template_name', $template->template_name, 'class="form-control tip" id="name" data-bv-notempty="true"'); ?>
        </div>
    </div>
   	<div class="col-md-6">
            <div class="form-group">
                <label for="product_details">Type</label>
                <?= form_dropdown('template_type', $templateType, $template->template_type, 'class="form-control" id="template_type"  '); ?>                
            </div>
        </div>
	<div class="col-md-6">
	    <div class="form-group">
	        <label for="product_details">Attchment</label>
	       <input id="image" type="file" data-browse-label="<?= lang('browse'); ?>" name="attachment" id="attachment-single" data-show-upload="false"
	                                   data-show-preview="false" accept="image/*" class="form-control file">      
	        <?php if( $template->attachment):?>                          
	        <a href="<?php echo base_url($template->attachment);?>" target="_blank"> <i class="fa fa-paperclip fa-lg" aria-hidden="true"></i> Attachment</a>
	        <?php endif?>
	    </div>
	</div>
        <div id="sms_template_keys" style="display:none;">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="template_key">Template Key</label>
                    <?php echo form_input('template_key', $template->template_key, 'class="form-control tip" id="template_key" data-bv-notempty="true"'); ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="dlt_te_id">Template DLT_TE_IT</label>
                    <?php echo form_input('dlt_te_id', $template->dlt_te_id, 'class="form-control tip" id="dlt_te_id" data-bv-notempty="true"'); ?>
                </div>
            </div>
        </div>
    <div class="col-md-12">
        <div class="form-group">
           <label for="product_details">Content</label>
            <textarea name="template_content" cols="40" rows="12" class="form-control" id="template_content_e"><?php echo $template->template_content?></textarea>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group sms_text_counter" style="display:none">
           <div class="sms_note blue"> <span id="max_sms_charsE" class="max_sms_charsE"></span> </div> 
	   <div class="sms_note" style="border: 1px solid;padding: 1.5em;font-size: 12px;color: initial;"> 
	   	<span class="sms_previewE">Dear Customer, <div class="sms_preview_dE" style="display:inline;">##message##</div> Thanks and regards.</span>
	    </div> 
	</div>
    </div>  
    <div class="col-md-12">
        <div class="form-group" >
           <?php echo form_submit('edit_template', lang('Update Template'), 'class="btn btn-primary" style="float:left"'); ?>
        </div>
    </div>    
    
   
    
    </div>     
</div>

<script type="text/javascript">
    $(document).ready(function (e) {
    
    load_template_options();
    
    $('#template_type').on('change', function() {
 	load_template_options();
    })
    
    function load_template_options(){
        
        var  template_type1 = $('#template_type').val();
 	 if(template_type1 !=1){
 	    $('.sms_text_counter').css("display","none");
            $('#sms_template_keys').css("display","none");
 	 }
 	 else{
 	    $('.sms_text_counter').css("display","block");
 	    $('#sms_template_keys').css("display","block");
 	 }

    }

         $('#template_content_e').keyup(function (event) {    	 
	    	 template_type = $('#template_type').val();
	    	 console.log('test'+template_type);
	    	 if(template_type ==1){
	    	      $('.sms_text_counter').css("display","block");
		      var lengthE = $(this).val().length+34;
		      var smsLength = <?php echo $sms_text_limit?>;
	              $('.sms_text_counter .max_sms_charsE').text('SMS count : '+Math.ceil(lengthE/smsLength )+' ');    
	              $('.sms_text_counter .sms_preview_dE').text($(this).val()+lengthE+''+smsLength);	              
	              if($(this).val()==''){
	              	 $('.sms_text_counter').css('display','none');
	              }
	    	 }        
        });
        
    
    
       $('#edit-template-form').submit(function( event ) {
         $('#form_loader').html('Loding..');
         $.ajax({
                type: "POST",
                url:  $('#edit-template-form').attr('action'),
                data: new FormData(this),
                dataType: "json",
                contentType: false,
                cache: false, // To unable request pages to be cached
                processData: false,
                beforeSend: function(){
                    $("#form_loader").html('<div class="alert alert-info"><button data-dismiss="alert" class="close" type="button">×</button><i class="fa fa-refresh fa-spin fa-fw"></i> <span >Loading...</span></span></div>');
                },
                success: function (data) {
                    if(data['error']){
                         $('#form_loader').html('<div class="alert alert-danger"><button data-dismiss="alert" class="close" type="button">×</button>'+data['error']+'</div>');
                    }
                    else{
                         $('#form_element').html('');   
                         $('#form_loader').html('<div class="alert alert-success"><button data-dismiss="alert" class="close" type="button">×</button>'+data['msg']+'</div>');
                    }
                    
                 },
                error: function () {
                }
            });  
        event.preventDefault();
       });
        
    });
</script>
