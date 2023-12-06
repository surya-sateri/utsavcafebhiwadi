<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$user_warehouse = $this->session->userdata('warehouse_id');
$v = $v1 = "";
/* if($this->input->post('name')){
  $v .= "&product=".$this->input->post('product');
  } */
if($this->input->post('product'))
{
    $v .= "&product=" . $this->input->post('product');
}
if($this->input->post('reference_no'))
{
    $v .= "&reference_no=" . $this->input->post('reference_no');
}
if($this->input->post('customer'))
{
    $v .= "&customer=" . $this->input->post('customer');
}
if($this->input->post('biller'))
{
    $v .= "&biller=" . $this->input->post('biller');
}
if($this->input->post('warehouse'))
{
    $v .= "&warehouse=" . $this->input->post('warehouse');
}else{
    $v .=($user_warehouse=='0' ||$user_warehouse==NULL)?'': "&warehouse=" . str_replace(",", "_",$user_warehouse);
}
if($this->input->post('user'))
{
    $v .= "&user=" . $this->input->post('user');
}
if($this->input->post('serial'))
{
    $v .= "&serial=" . $this->input->post('serial');
}

if($this->input->post('gstn_opt'))
{
    $v .= "&gstn_opt=" . $this->input->post('gstn_opt');
}
if($this->input->post('gstn_no'))
{
    $v .= "&gstn_no=" . $this->input->post('gstn_no');
}
if($this->input->post('hsn_code'))
{
    $v .= "&hsn_code=" . $this->input->post('hsn_code');
}
if($this->input->post('max_export_sales'))
{
    $v .= "&max_export_sales=" . $this->input->post('max_export_sales');
}

if($this->input->post('start_date'))
{
    $v1 = $v;
    $st = $this->sma->fld($this->input->post('start_date')) . ":00";
    $v1 .= "&start_date=" . strtotime($st);
    $v .= "&start_date=" . $this->input->post('start_date');
    if(empty($this->input->post('end_date')))
    {
        $v .= "&end_date=" . date("d/m/Y") . ' 23:55';
        $_POST['end_date'] = date("d/m/Y") . ' 23:55';
    }

}/* else {
    $start_date = date('d/m/Y', strtotime("-30 days")).' 00:00';
    $v .= "&start_date=" . $start_date;
  
}*/


if($this->input->post('end_date'))
{
    $et = $this->sma->fld($this->input->post('end_date')) . ":00";
    $v1 .= "&end_date=" . strtotime($et);

    $v .= "&end_date=" . $this->input->post('end_date');
}/* else{
   
    $end_date = date('d/m/Y H:i');
      $v .= "&end_date=" . $end_date;
}*/
?>
<style>
    #clear_customer {
        position: absolute;
        right: 40px;
        top: 35px;
    }
</style>
<script>
    $(document).ready(function () {


        $("#clear_customer").click(function () {

            $("#customer").select2("val", "");
        });


        var oTable = $('#SlRData').dataTable({
            "aaSorting": [[0, "desc"]],
            "aLengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
            'sAjaxSource': '<?= site_url('reports/getSalesReportCnew/?v=1' . $v) ?>',
            'fnServerData': function (sSource, aoData, fnCallback) {
                aoData.push({
                    "name": "<?= $this->security->get_csrf_token_name() ?>",
                    "value": "<?= $this->security->get_csrf_hash() ?>"
                });
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
            'fnRowCallback': function (nRow, aData, iDisplayIndex) {
                nRow.id = aData[13];
                nRow.className = (aData[8] > 0) ? "invoice_link2" : "invoice_link2 warning";
               
                /* sales item Column Add*/
                var nCells = nRow.getElementsByTagName('td');
                var id = nRow.id; 
                //console.log(id);
                 var url = '<?= site_url("reports/getSalesItemsGst/") ?>?id='+id;
                    $.ajax({
                    type:'ajax',
                    dataType:'json',
                    url:url,
                    async:true,
                    success:function(result){
                  //  console.log(result);
                        nCells[13].innerHTML =  result['hsn_code'];
                        nCells[14].innerHTML =  result['qty'];
                        nCells[15].innerHTML =  result['units'];
                        nCells[16].innerHTML =  result['CGST'];
                        nCells[17].innerHTML =  result['SGST'];
                        nCells[18].innerHTML =  result['IGST'];
                        nCells[19].innerHTML =  result['tax'];
                     
                    }, error:function(){
                       //nCells[9].innerHTML = currencyFormat(0);
                    }
                });
                /***/
               return nRow;
            },
            "aoColumns": [
                null,null,
                null,
                null,
                null,
                null,
                null,
                {"mRender": currencyFormat},
                {"mRender": currencyFormat},
                {"mRender": currencyFormat},
                {"mRender": currencyFormat},
                null,
                {"mRender": row_status},
                null,
                null,
                null,
                null,
                null,
                null,null
             ],
            "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
                var c_gst = 0, s_gst = 0, i_gst = 0, gtotal = 0, paid = 0, balance = 0, taxable_amount=0, tax_rate=0;
                //console.log(aaData);
                for (var i = 0; i < aaData.length; i++) {
                    gtotal += parseFloat(aaData[aiDisplay[i]][7]);
                    taxable_amount += parseFloat(aaData[aiDisplay[i]][8]);
                    //tax_rate += parseFloat(aaData[aiDisplay[i]][12]);
                    paid += parseFloat(aaData[aiDisplay[i]][9]);
                    balance += parseFloat(aaData[aiDisplay[i]][10]);
                }
              
                var nCells = nRow.getElementsByTagName('th');
                nCells[7].innerHTML = currencyFormat(parseFloat(gtotal));
                nCells[8].innerHTML = currencyFormat(parseFloat(taxable_amount));
                //nCells[12].innerHTML = currencyFormat(parseFloat(gtotal));
                nCells[9].innerHTML = currencyFormat(parseFloat(paid));
                nCells[10].innerHTML = currencyFormat(parseFloat(balance));
               
                
            }
        }).fnSetFilteringDelay().dtFilter([
            {
                column_number: 0,
                filter_default_label: "[<?=lang('date');?> (yyyy-mm-dd)]",
                filter_type: "text",
                data: []
            },
        ], "footer");
    });
</script>
<script type="text/javascript">
    $(document).ready(function () {
        $("#SlRData_length .select").remove();
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
                quietMillis: 17,
                data: function (term, page) {
                    return {
                        term: term,
                        limit: 10
                    };
                },
                results: function (data, page) {
                    if (data.results != null) {
                        alert('<?php echo $this->input->post('customer')?>');
                        return {results: data.results};
                    } else {
                        return {results: [{id: '', text: 'No Match Found'}]};
                    }
                }
            }
        });

        $('#customer').val('<?php echo $this->input->post('customer')?>').trigger('change')
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
        <h2 class="blue"><i class="fa-fw fa fa-heart"></i><?= lang('Gst_Sales_Report_New'); ?> <?php
            if($this->input->post('start_date'))
            {
                echo "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
            }
            ?>
        </h2>

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
                    <a data-toggle="dropdown" class="dropdown-toggle tip" href="#" title="<?= lang('sales_tax_summary') ?>">
                        <i class="icon fa fa-tasks tip" data-placement="left"
                           ></i>
                    </a>
                    <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
                        <li>
                            <a href="<?= site_url('reports/sales_tax_report_ajax/?v=1' . $v1) ?>" data-toggle="modal"
                               data-target="#myModal">
                                <i class="fa fa-file-o"></i> <?= lang('sales_tax_summary') ?>
                            </a>
                        </li>
                    </ul>
                </li>
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
                    <a href="#" id="image" class="tip" title="<?= lang('save_image') ?>">
                        <i class="icon fa fa-file-picture-o"></i>
                    </a>
                </li>

            </ul>
        </div>
    </div>
<p class="introtext"><?= lang('customize_report'); ?></p>
    <div class="box-content">
        <div class="row">
            <div class="col-lg-12 table-responsive">

                

                <div id="form" >

                    <?php echo form_open("reports/sales_gst_reportnew"); ?>
                    <div class="row">

                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="reference_no"><?= lang("reference_no"); ?></label>
                                <?php echo form_input('reference_no', (isset($_POST['reference_no']) ? $_POST['reference_no'] : ""), 'class="form-control tip" id="reference_no"'); ?>

                            </div>
                        </div>

                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="user"><?= lang("created_by"); ?></label>
                                <?php
                                $us[""] = lang('select') . ' ' . lang('user');
                                foreach($users as $user)
                                {
                                    $us[$user->id] = $user->first_name . " " . $user->last_name;
                                }
                                echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-control" id="user" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("user") . '"');
                                ?>
                            </div>
                        </div>
                        <div class="col-sm-4" style="position: relative;">
                            <div class="form-group">
                                <label class="control-label" for="customer"><?= lang("customer"); ?></label>
                                <?php echo form_input('customer', (isset($_POST['customer']) ? $_POST['customer'] : ""), 'class="form-control" id="customer" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("customer") . '"'); ?>
                                <a href="javascript:void(0);" id="clear_customer"><i class="fa fa-refresh"
                                                                                     aria-hidden="true"></i></a>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="biller"><?= lang("biller"); ?></label>
                                <?php
                                $bl[""] = lang('select') . ' ' . lang('biller');
                                foreach($billers as $biller)
                                {
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
                                foreach($warehouses as $warehouse)
                                {
                                    if($Owner || $Admin){
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
                                    <?= lang("date_range_sales", "date_range_sales"); ?>
                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="text"
                                               value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] . '-' . $_POST['end_date'] : ""; ?>"
                                               id="daterange_new" class="form-control">
                                        <span class="input-group-addon" style="display:none;"><i class="fa fa-chevron-down"></i></span>
                                        <input type="hidden" name="start_date" id="start_date"
                                               value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : ""; ?>">
                                        <input type="hidden" name="end_date" id="end_date"
                                               value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ""; ?>">

                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-sm-4">
                            <div class="form-group">
                                <?= lang("gstn", "gstn"); ?>
                                <?php echo form_input('gstn_no', (isset($_POST['gstn_no']) ? $_POST['gstn_no'] : ""), 'class="form-control" id="gstn_no"'); ?>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label" for="gstn"> With <?= lang("gstn"); ?></label>
                                <?php
                                $gstnOpt["0"] = 'ALL';
                                $gstnOpt["-1"] = 'No';
                                $gstnOpt["1"] = 'Yes';

                                echo form_dropdown('gstn_opt', $gstnOpt, (isset($_POST['gstn_opt']) ? $_POST['gstn_opt'] : "0"), 'class="form-control" id="gstn_opt" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("gstn") . ' Option"');
                                ?>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <?= lang("hsn_code", "hsn_code"); ?>
                                <?php echo form_input('hsn_code', (isset($_POST['hsn_code']) ? $_POST['hsn_code'] : ""), 'class="form-control" id="hsn_code"'); ?>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label><?= lang("Maximum Export Recent Sale"); ?></label>
                               <!-- <?php
                                $maxexportsales["0-500"]  = '0 To 500';
                                $maxexportsales["0-1000"] = '0 To 1000'; 
                                $maxexportsales["0-2000"] = '0 To 2000';                                
                                $maxexportsales["0-3000"] = '0 To 3000';                               
                                $maxexportsales["501-500"] = '501 To 1000';
                                $maxexportsales["1001-1000"] = '1001 To 2000';
                                $maxexportsales["2001-1000"] = '2001 To 3000';
                                $maxexportsales["3001-1000"] = '3001 To 4000';
                                $maxexportsales["4001-1000"] = '4001 To 5000';
                                $maxexportsales["5001-1000"] = '5001 To 6000';
                                $maxexportsales["6001-1000"] = '6001 To 7000';
                                $maxexportsales["7001-1000"] = '7001 To 8000';
                                $maxexportsales["8001-1000"] = '8001 To 9000';
                                $maxexportsales["9001-1000"] = '9001 To 10000';

                               // echo form_dropdown('max_export_sales', $maxexportsales, (isset($_POST['max_export_sales']) ? $_POST['max_export_sales'] : "0"), 'class="form-control" id="max_export_sales" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("max_export_sales") . ' Option"');
                                ?> -->

                                <?php $startcount=0;$count=$salegstcount;$addcount =200;$endcount=200;$seccount=0;?>
                                 <select class="form-control" name="max_export_sales" id="max_export_sales">
                                 <option value="0">Select</option>
                                 <?php
                                 for ( $startcount=0; $count>=$startcount; $startcount = $startcount+$endcount ) {
                                       $seccount = $startcount + $endcount; ?>
                                  <option value="<?php echo $startcount.'-'.$endcount; ?>"><?php echo $startcount.'-'.$seccount; ?></option>
                                  <?php  }  ?>
                                </select>
                          </div>
                        </div>


                    </div>
                    <div class="form-group">
                        <div
                                class="controls"> <?php echo form_submit('submit_report', $this->lang->line("submit"), 'class="btn btn-primary"'); ?>
                            <a href="<? echo base_url('reports/sales_gst_reportnew'); ?>" class="btn btn-success">Reset
                                Filter</a></div>
                        <div></div>
                    </div>
                    <?php echo form_close(); ?>

                </div>
                <div class="clearfix"></div>

                <div class="table-responsive">
                    <table id="SlRData" class="table table-bordered table-hover table-striped table-condensed reports-table">
                        <thead>
                            <tr> 
                            <th><?= lang("date"); ?></th>
                            <th><?= lang("Invoice No"); ?></th>
                            <th><?= lang("reference_no"); ?></th>
                            <th><?= lang("biller"); ?></th>
                            <th><?= lang("customer"); ?></th>
                            <th><?= lang("State_code"); ?></th>
                            <th><?= lang("gstn"); ?></th>
                            <th><?= lang("Invoice_Value"); ?></th>
                            <th><?= lang("Taxable_Amount"); ?></th>
                            <th><?= lang("paid"); ?></th>
                            <th><?= lang("balance"); ?></th>
                            <th><?= lang("Payment Method"); ?></th>
                            <th><?= lang("payment_status"); ?></th>
                            <th><?= lang("hsn_code", "hsn_code"); ?></th>
                            <th><?= lang("Qty"); ?></th>
                            <th><?= lang("unit"); ?></th>
                            <th><?= lang("CGST"); ?></th>
                            <th><?= lang("SGST"); ?></th>
                            <th><?= lang("IGST"); ?></th>
                            <th><?= lang("GST_Rate"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="19" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
                        </tr>
                        </tbody>
                        <tfoot class="dtFilter">
                        <tr class="active">
                            <th></th><th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th><?= lang("Invoice_Value"); ?></th>
                            <th><?= lang("Taxable_Amount"); ?></th>
                            <th><?= lang("paid"); ?></th>
                            <th><?= lang("balance"); ?></th>
                            <th></th>
                            <th>Status</th>
                             <th></th>
                            <th></th>
                            <th></th>
                            <th>CGST</th>
                            <th>SGST</th>
                            <th>IGST</th>
                            <th><?= lang("GST_Rate"); ?></th>
                            
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
            window.location.href = "<?=site_url('reports/getSalesReportCnew/pdf/?v=1' . $v)?>";
            return false;
        });
        $('#xls').click(function (event) {
            event.preventDefault();
            window.location.href = "<?=site_url('reports/getSalesReportCnew/0/xls/?v=1' . $v)?>";
            return false;
        });
        $('#image').click(function (event) {
             event.preventDefault();
            window.location.href = "<?=site_url('reports/getSalesReportCnew/0/0/xls/?v=1' . $v)?>";
            return false;
        });
    });
</script>