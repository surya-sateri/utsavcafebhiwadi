<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$user_warehouse = $this->session->userdata('warehouse_id');
$permisions_werehouse = explode(",", $this->session->userdata('warehouse_id'));
$v = "";
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
if ($this->input->post('start_date')) {
    $v .= "&start_date=" . $this->input->post('start_date');
}
if ($this->input->post('end_date')) {
    $v .= "&end_date=" . $this->input->post('end_date');
}
if ($this->input->post('filter_sale_type')) {
    $v .= "&filter_sale_type=" . $this->input->post('filter_sale_type');
}

?>

<script>
    $(document).ready(function () {
        var oTable = $('#SlRData').dataTable({
            "aaSorting": [[0, "desc"]],
            "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
            'sAjaxSource': '<?= site_url('reports/getCustomerSalesReport/?v=1'. $v) ?>',
            'fnServerData': function (sSource, aoData, fnCallback) {
                aoData.push({
                    "name": "<?= $this->security->get_csrf_token_name() ?>",
                    "value": "<?= $this->security->get_csrf_hash() ?>"
                });
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
            'fnRowCallback': function (nRow, aData, iDisplayIndex) {
                nRow.id = aData[0]; 
               // nRow.className = (aData[6] > 0) ? "invoice_link2" : "invoice_link2 warning";
                //return nRow;
            },
            "aoColumns": [{"bVisible": false}, null, null, null,  {"mRender": currencyFormat}],
            "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
                var gtotal = 0;
                for (var i = 0; i < aaData.length; i++) {
                    gtotal += parseFloat(aaData[aiDisplay[i]][4]);
                }
				
                var nCells = nRow.getElementsByTagName('th');
                nCells[3].innerHTML = currencyFormat(parseFloat(gtotal));
            }
        }).fnSetFilteringDelay().dtFilter([
            {column_number: 0, filter_default_label: "[<?=lang('customer');?>]", filter_type: "text", data: []},
        ], "footer");
    });
</script>
<script type="text/javascript">
    $(document).ready(function () {
        $('#form').hide();
        <?php if ($this->input->post('customer')) { ?>
        $('#customer').val(<?= $this->input->post('customer') ?>).select2({
            minimumInputLength: 1,
            data: [],
            initSelection: function (element, callback) {
                $.ajax({
                    type: "get", async: false,
                    url: site.base_url + "customers/suggestions/" + $(element).val(),
                    dataType: "json",
                    success: function (data) {
                        callback(data.results[0]);
                    }
                });
            },
            ajax: {
                url: site.base_url + "customers/suggestions",
                dataType: 'json',
                quietMillis: 15,
                data: function (term, page) {
                    return {
                        term: term,
                        limit: 10
                    };
                },
                results: function (data, page) {
                    if (data.results != null) {
                        return {results: data.results};
                    } else {
                        return {results: [{id: '', text: 'No Match Found'}]};
                    }
                }
            }
        });

        $('#customer').val(<?= $this->input->post('customer') ?>);
        <?php } ?>
        $('.toggle_down').click(function () {
            $("#form").slideDown();
            return false;
        });
        $('.toggle_up').click(function () {
            $("#form").slideUp();
            return false;
        });
    });
</script>


<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-heart"></i><?= lang('Customer_wise_Sale'); ?> <?php
            if ($this->input->post('start_date')) {
                echo "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
            }
            ?>
        </h2>
         <div  >
               <?php   if ($_POST['start_date']) {?>
            <div class="col-sm-2" >
               <?php }else{?>
            <div class="col-sm-offset-2 col-sm-3" > 
                <?php } ?>
                <h4 class="control-label" for="sales">Pdf/Excel download limit</h4></div>
                <?php $startcount=0;$count=$salecount;$addcount =500;$endcount=500;$seccount=0;
                ?>
                <div class="col-sm-2">
                    
                <select class="form-control" name="limitpdf" id="limitpdf">
                <option value="0">Select</option>
                <?php
                    for ( $startcount=0; $count>=$startcount; $startcount = $startcount+$endcount ) {
                        $seccount = $startcount + $endcount;
                ?>
                <option value="<?php echo $startcount.'-'.$endcount; ?>"><?php echo $startcount.'-'.$seccount; ?></option>
               <?php  }  ?>
            </select>
            </div>
        </div>
        <div class="box-icon">
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
        </div>
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
                <!--<li class="dropdown">
                    <a href="#" id="image" class="tip" title="<?= lang('save_image') ?>">
                        <i class="icon fa fa-file-picture-o"></i>
                    </a>
                </li>-->
            </ul>
        </div>
    </div>
<p class="introtext"><?= lang('customize_report'); ?></p>
    <div class="box-content">
        <div class="row">
            <div class="col-lg-12">

                

                <div id="form">

                    <?php echo form_open("reports/get_customer_wise_sales"); ?>
                    <div class="row">
                        <!--  <div class="col-sm-4">
                          <div class="form-group">
                                <?= lang("product", "suggest_product"); ?>
                                <?php echo form_input('sproduct', (isset($_POST['sproduct']) ? $_POST['sproduct'] : ""), 'class="form-control" id="suggest_product"'); ?>
                                <input type="hidden" name="product" value="<?= isset($_POST['product']) ? $_POST['product'] : "" ?>" id="report_product_id"/>
                            </div>
                        </div>-->
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="user"><?= lang("created_by"); ?></label>
                                <?php
                                $us[""] = lang('select').' '.lang('user');
                                foreach ($users as $user) {
                                    $us[$user->id] = $user->first_name . " " . $user->last_name;
                                }
                                echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-control" id="user" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("user") . '"');
                                ?>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="customer"><?= lang("customer"); ?></label>
                                <?php echo form_input('customer', (isset($_POST['customer']) ? $_POST['customer'] : ""), 'class="form-control" id="customer" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("customer") . '"'); ?>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="biller"><?= lang("biller"); ?></label>
                                <?php
                                $bl[""] = lang('select').' '.lang('biller');
                                foreach ($billers as $biller) {
                                    $bl[$biller->id] = $biller->company != '-' ? $biller->company : $biller->name;
                                }
                                echo form_dropdown('biller', $bl, (isset($_POST['biller']) ? $_POST['biller'] : ""), 'class="form-control" id="biller" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("biller") . '"');
                                ?>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="warehouse"><?= lang("warehouse"); ?></label>
                                <?php
                                $permisions_werehouse = explode(",", $user_warehouse);
                                $wh[""] = lang('select') . ' ' . lang('warehouse');
                                foreach ($warehouses as $warehouse) {
                                    if($Owner || $Admin ){
                                        $wh[$warehouse->id] = $warehouse->name;
                                    }else if(in_array($warehouse->id,$permisions_werehouse)){
                                        $wh[$warehouse->id] = $warehouse->name;
                                    }    
                                }
                                echo form_dropdown('warehouse', $wh, (isset($_POST['warehouse']) ? $_POST['warehouse'] : ""), 'class="form-control" id="warehouse" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("warehouse") . '"');
                                ?>
                            </div>
                        </div>
                        
                         <div class="col-sm-4">                        
                            <div class="form-group choose-date hidden-xs">
		                <div class="controls">
		                    <?= lang("date_range", "date_range"); ?>
		                    <div class="input-group">
		                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                      
		                        <input type="text"
                                               autocomplete="off"
		                               value="<?php echo ($_POST['start_date'] !="") ? $_POST['start_date'].'-'.$_POST['end_date'] : "";?>"
		                               id="daterange_new" class="form-control">
		                        <span class="input-group-addon" style="display:none;"><i class="fa fa-chevron-down"></i></span>
		                         <input type="hidden" name="start_date"  id="start_date" value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : "";?>">
		                         <input type="hidden" name="end_date"  id="end_date" value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : "";?>" >
                                    </div>
		                </div>
		            </div>
                        </div>
                       <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="filter_sale_type"><?= lang("Filter Sale"); ?></label>
                                <?php
                                $us_sale[""] = lang('select').' '.lang('Sale');
                                $us_sale['No_sale'] = 'No sale';
                                $us_sale['Top_Sale'] = 'Top Sale';
                                $us_sale['Bottom_Sale'] = 'Bottom Sale';
                                echo form_dropdown('filter_sale_type', $us_sale, (isset($_POST['filter_sale_type']) ? $_POST['filter_sale_type'] : ""), 'class="form-control" id="filter_sale_type" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("Filter Sale") . '"');
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="controls"> 
                            <?php echo form_submit('submit_report', $this->lang->line("submit"), 'class="btn btn-primary"'); ?>
                            
                            <!--<input type="button" id="report_reset" data-value="<?=base_url('reports/get_customer_wise_sales');?>" name="submit_report" value="Reset" class="btn btn-warning input-xs">-->
                            <a href="reports/restbutton" class="btn btn-success"  onClick="resetFunction();">Reset</a> 
                        </div>
                    </div>
                    <?php echo form_close(); ?>

                </div>
                <div class="clearfix"></div>

                <div class="table-responsive">
                    <table id="SlRData"
                           class="table table-bordered table-hover table-striped table-condensed reports-table">
                        <thead>
                        <tr>
							<th>Sr</th>
                            <th><?= lang("name"); ?></th>
                            <th><?= lang("email"); ?></th>
                            <th><?= lang("phone"); ?></th>
                            <th><?= lang("grand_total"); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="5" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
                        </tr>
                        </tbody>
                        <tfoot class="dtFilter">
                        <tr class="active">
                            <th>Sr</th>
                            <th>Customer Name</th>
                            <th>Customer Email</th>
                            <th>Customer Phone</th>
                            <th>Grand Total</th>
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
               window.location.href = "<?=site_url('reports/getCustomerSalesReport/pdf/?v=1'.$v)?>"+limitcnt;
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
              window.location.href = "<?=site_url('reports/getCustomerSalesReport/0/xls/?v=1'.$v)?>"+limitcnt;
              $("#limitpdf").val(0).change(); 
              return false;
              
             
            }
        });

        $('#image').click(function (event) {
            event.preventDefault();
			  window.location.href = "<?=site_url('reports/getCustomerSalesReport/0/0/img/?v=1'.$v)?>";
            return false;
        });
    });

    function resetFunction(){
       $('form#search-form input[type=hidden].search-value').val('');
     // location.reload(true);
    }
</script>
