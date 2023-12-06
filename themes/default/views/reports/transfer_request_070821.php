<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$user_warehouse = $this->session->userdata('warehouse_id');

$permisions_werehouse = explode(",", $this->session->userdata('warehouse_id'));

$v = "";
/* if($this->input->post('name')){
  $v .= "&product=".$this->input->post('product');
  } */
if ($this->input->post('product')) {
    $v .= "&product=" . $this->input->post('product');
}
if ($this->input->post('reference_no')) {
    $v .= "&reference_no=" . $this->input->post('reference_no');
}
if ($this->input->post('customer')) {
    $v .= "&customer=" . $this->input->post('customer');
}
if ($this->input->post('biller')) {
    $v .= "&biller=" . $this->input->post('biller');
}
if ($this->input->post('warehouse')) {
    $v .= "&warehouse=" . $this->input->post('warehouse');
}else{
    $v .=($user_warehouse=='0' ||$user_warehouse==NULL)?'':"&warehouse=" . str_replace(",", "_",$user_warehouse);
}
if ($this->input->post('user')) {
    $v .= "&user=" . $this->input->post('user');
}
if ($this->input->post('serial')) {
    $v .= "&serial=" . $this->input->post('serial');
}
if ($this->input->post('start_date')) {
    $v .= "&start_date=" . $this->input->post('start_date');
}
if ($this->input->post('end_date')) {
    $v .= "&end_date=" . $this->input->post('end_date');
}

?>

<script>
    $(document).ready(function () {
        var oTable = $('#SlRData').dataTable({
            "aaSorting": [[0, "desc"]],
            "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
            'sAjaxSource': '<?= site_url('reports/gettransfer_request/?v=1'. $v) ?>',
            'fnServerData': function (sSource, aoData, fnCallback) {
                aoData.push({
                    "name": "<?= $this->security->get_csrf_token_name() ?>",
                    "value": "<?= $this->security->get_csrf_hash() ?>"
                });
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
            'fnRowCallback': function (nRow, aData, iDisplayIndex) {
                $( nRow ).find('td:eq(2)').addClass('noprint');
                $( nRow ).find('td:eq(3)').addClass('noprint');
            },
            "aoColumns": [ null, null, null,null,  null,{
                     "bSearchable": false,
            },
  <?php 
   foreach($warehouses as $itemwarehouse){ ?>
   null,
<?php } ?> 

      ],
            "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {

            }
        }).fnSetFilteringDelay().dtFilter([
     ], "footer");
    });
</script>
<style>
    
    @media print {
         .noprint {
display:none !important;
               }
         .printdata{display: block !important;} 
        
    }
     
</style>
<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-heart"></i><?= lang('Transfer Request Report'); ?> <?php
            if ($this->input->post('start_date')) {
                echo "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
            }
            ?>
        </h2>
        
<!--        <div class="box-icon">
            <ul class="btn-tasks">
                <li class="dropdown">
                    <a href="#" class="toggle_up tip" title="<?= lang('hide_form') ?>">
                        <i class="icon fa fa-toggle-up"></i>
                    </a>
                </li>
                <li class="dropdown">
                    <a href="#" class="toggle_down tip" title="<?= lang('show_form') ?>">
                        <i class="icon fa fa-toggle-down"></i>
                    </a>
                </li>
            </ul>
        </div>-->
        <div class="box-icon">
            <ul class="btn-tasks">
                <li class="dropdown">
                    <a href="#" id="pdf" class="tip" title="<?= lang('download_pdf') ?>">
                        <i class="icon fa fa-file-pdf-o"></i>
                    </a>
                </li>
                <li class="dropdown">
                    <a href="#" id="xls" class="tip" title="<?= lang('download_xls') ?>">
                        <i class="icon fa fa-file-excel-o"></i>
                    </a>
                </li>
                <li class="dropdown">
                    <span class="tip" style="color: #5993cb;"  onclick="myprint()" /><i class=" icon fa fa-print"></i> </span>
                </li>
            </ul>
        </div>
    </div>
<p class="introtext"><?= lang('customize_report'); ?></p>
    <div class="box-content">
        <div class="row">
            <div class="col-lg-12">

                

                <div id="form">

                    <?php echo form_open("reports/sales"); ?>
                    <div class="row">
<!--                        <div class="col-sm-4">
                            <div class="form-group">
                                <?= lang("product", "suggest_product"); ?>
                                <?php echo form_input('sproduct', (isset($_POST['sproduct']) ? $_POST['sproduct'] : ""), 'class="form-control" id="suggest_product"'); ?>
                                <input type="hidden" name="product" value="<?= isset($_POST['product']) ? $_POST['product'] : "" ?>" id="report_product_id"/>
                            </div>
                        </div>-->
                        

                    </div>
<!--                    <div class="form-group">
                        <div class="controls"> 
                            <?php echo form_submit('submit_report', $this->lang->line("submit"), 'class="btn btn-primary"'); ?>
                            
                            <input type="button" id="report_reset" data-value="<?=base_url('reports/sales');?>" name="submit_report" value="Reset" class="btn btn-warning input-xs">
                            <a href="reports/restbutton" class="btn btn-success"  onClick="resetFunction();">Reset</a> 
                        </div>
                    </div>-->
                    <?php echo form_close(); ?>

                </div>
                <div class="clearfix"></div>

                <div class="table-responsive">
                         <h2 class="printdata text-center" style="display:none"> Transfer Request Report </h2>
                    <table id="SlRData"
                           class="table table-bordered table-hover table-striped table-condensed reports-table">
                        <thead>
                        <tr>
                            <th><?= lang("Product Name"); ?></th>
                            <th><?= lang("Varient"); ?></th>
                            <th><?= lang("Category"); ?></th>
                            <th><?= lang("Sub Category"); ?></th>
                            <th><?= lang("Brand"); ?></th>
                            <th><?= lang("Total Request Qty"); ?></th>
                            <?php
                             $col =5;
                            foreach($warehouses as $itemwarehouse){ ?>
                              <th><?= $itemwarehouse->name ?></th>  
                            <?php $col++; } ?>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="<?= $col ?>" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
                        </tr>
                        </tbody>
                        <tfoot class="dtFilter">
                        <tr class="active">
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                           <th></th>
                             <?php
                            
                            foreach($warehouses as $itemwarehouse){ ?>
                              <th><?= $itemwarehouse->name ?></th>  
                            <?php  } ?>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/html2canvas.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('#pdf').click(function (event) {
            event.preventDefault();
            var limitcnt =  $("#limitpdf option:selected").val();
            
            if(limitcnt=='0'){
              alert('Please Select Pdf/Excel limit');
            }else{
              <?php 
              $v .= "&strtlimit="?>
               window.location.href = "<?=site_url('reports/gettransfer_request/pdf/?v=1'.$v)?>";
               $("#limitpdf").val(0).change();
              return false;
            }
           
        });

        $('#xls').click(function (event) {
          
            event.preventDefault();
            var limitcnt =  $("#limitpdf option:selected").val();
            if(limitcnt=='0'){
              alert('Please Select Pdf/Excel limit');
            }else{
              <?php 
              $v .= "&strtlimit="?>
              window.location.href = "<?=site_url('reports/gettransfer_request/0/xls/?v=1'.$v)?>";
              $("#limitpdf").val(0).change(); 
              return false;
              
             
            }
            // event.preventDefault();
            // window.location.href = "<?=site_url('reports/gettransfer_request/0/xls/?v=1'.$v)?>";
            // return false;
        });

        $('#image').click(function (event) {
            event.preventDefault();
			  window.location.href = "<?=site_url('reports/getSalesReport/0/0/img/?v=1'.$v)?>";
			/*
            html2canvas($('.box'), {
                onrendered: function (canvas) {
                    //var img = canvas.toDataURL()
                    //window.open(img);
					 var myImage = canvas.toDataURL("image/png");
					 window.open(myImage);
                }
            });*/
            return false;
        });
    });

    function resetFunction(){
       $('form#search-form input[type=hidden].search-value').val('');
     // location.reload(true);
    }

     function myprint() {
    window.print();
  }

</script>