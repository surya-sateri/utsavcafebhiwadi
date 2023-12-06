<?php
$v='';
    if ($this->input->post('start_date')) {
        $startDate = explode('/', substr($this->input->post('start_date') , 0, 10));
        $start_date = $startDate[2] . "-" . $startDate[1] . "-" . $startDate[0] . "  00:00";
        $v .= "&start_date=" . $start_date;
    }
    if ($this->input->post('end_date')) {
        $endDate = explode('/', substr($this->input->post('end_date') , 0, 10));
        $end_date = $endDate[2] . "-" . $endDate[1] . "-" . $endDate[0]. "  23:59";
        $v .= "&end_date=" . $end_date;
    }
   
  
?>

<script>
    $(document).ready(function () {
        var pb = <?= json_encode($pb); ?>;
        function paid_by(x) {
            return (x != null) ? (pb[x] ? pb[x] : x) : x;
        }

        function ref(x) {
            return (x != null) ? x : ' ';
        }

        var oTable = $('#SalesGST').dataTable({
            "aaSorting": [[0, "desc"]],
            "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
            'sAjaxSource': '<?= site_url('reports/getTaxReports/?v=1' . $v) ?>',
            'fnServerData': function (sSource, aoData, fnCallback) {
                aoData.push({
                    "name": "<?= $this->security->get_csrf_token_name() ?>",
                    "value": "<?= $this->security->get_csrf_hash() ?>"
                });
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
           "aoColumns": [null, {"mRender": currencyFormat,"bSearchable": false}, {"mRender": currencyFormat,"bSearchable": false}, {"mRender": currencyFormat,"bSearchable": false}, {"mRender": currencyFormat,"bSearchable": false}, {"mRender": currencyFormat,"bSearchable": false}, {"mRender": currencyFormat,"bSearchable": false}],
           
           'fnRowCallback': function (nRow, aData, iDisplayIndex) {
                nRow.id = aData[0];
//                 nCells['0'].innerHTML = currencyFormat(0);
                 var nCells = nRow.getElementsByTagName('td');
                 nCells['0'].innerHTML = aData[0]+'%';
//                return nRow;
            },
            "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
                 var tbasicamt= 0, tsgst = 0, tcgst =0, tigst = 0, tgst = 0 , tsales = 0;
                for (var i = 0; i < aaData.length; i++) {
                    tbasicamt += parseFloat(aaData[aiDisplay[i]][1]);
                    tsgst += parseFloat(aaData[aiDisplay[i]][2]);
                    tcgst += parseFloat(aaData[aiDisplay[i]][3]);
                    tigst += parseFloat(aaData[aiDisplay[i]][4]);
                    tgst += parseFloat(aaData[aiDisplay[i]][5]);
                    tsales += parseFloat(aaData[aiDisplay[i]][6]);
                }
                var nCells = nRow.getElementsByTagName('th');
                nCells[1].innerHTML = 'Total';
                nCells[1].innerHTML = currencyFormat(parseFloat(tbasicamt));
                nCells[2].innerHTML = currencyFormat(parseFloat(tsgst));
                nCells[3].innerHTML = currencyFormat(parseFloat(tcgst));
                nCells[4].innerHTML = currencyFormat(parseFloat(tigst));
                nCells[5].innerHTML = currencyFormat(parseFloat(tgst));
                nCells[6].innerHTML = currencyFormat(parseFloat(tsales));
            }
        }).fnSetFilteringDelay().dtFilter([
            {column_number: 0, filter_default_label: "<?=lang('Total');?> ", filter_type: "text", data: []},
            //{column_number: 1, filter_default_label: "[<?=lang('Basic Amt. ');?>]", filter_type: "text", data: []},
            //{column_number: 2, filter_default_label: "[<?=lang('SGST');?>]", filter_type: "text", data: []},
            //{column_number: 3, filter_default_label: "[<?=lang('CGST');?>]", filter_type: "text", data: []},
           // {column_number: 4, filter_default_label: "[<?=lang('IGST');?>]", filter_type: "text", data: []},
            //{column_number: 5, filter_default_label: "[<?=lang('Total GST');?>]", filter_type: "text", data: []},
           // {column_number: 6, filter_default_label: "[<?=lang('Total Sales');?>]", filter_type: "text", data: []},
        ], "footer");

    });
</script>


<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-money"></i><?= lang('Tax Reports'); ?> <?php
            if ($this->input->post('start_date')) {
                echo "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
            } ?>
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
                    <a href="#" id="pdf" class="tip" title="<?= lang('download_pdf') ?>  ">
                        <i class="icon fa fa-file-pdf-o"></i>
                    </a>
                </li>
                <li class="dropdown">
                    <a href="#" id="xls" class="tip" title="<?= lang('download_xls') ?>  ">
                        <i class="icon fa fa-file-excel-o"></i>
                    </a>
                </li>
                
                
            </ul>
        </div>
    </div>
<p class="introtext"><?= lang('customize_report'); ?></p>

    <div class="box-content">
        <div class="row">
            <div class="col-lg-12">

                
                <div id="form">

                    <?php echo form_open("reports/taxreports","id='searchform'"); ?>
                    <div class="row">
                       
                        <div class="col-sm-4">                        
                            <div class="form-group choose-date hidden-xs">
		                <div class="controls">
		                    <?= lang("date_range", "date_range"); ?>
		                    <div class="input-group">
		                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
		                        <input type="text"
		                               value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'].'-'.$_POST['end_date'] : "";?>"
                                               id="daterange_new" class="form-control" autocomplete="off">
		                        <!--<span class="input-group-addon"><i class="fa fa-chevron-down"></i></span>-->
		                         <input type="hidden" name="start_date"  id="start_date" value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : "";?>">
		                         <input type="hidden" name="end_date"  id="end_date" value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : "";?>" >
                                    </div>
		                </div>
		            </div>
                        </div>
         
                    </div>
                    <div class="form-group">
                        <div class="controls">
                            <?php echo form_submit('submit_report', $this->lang->line("submit"), 'class="btn btn-primary"'); ?>
                            
                            <a href="<?= site_url('reports/taxreports') ?>" type="reset" id="report_reset"  class="btn btn-warning input-xs">Reset </a>
                        </div>
                    </div>
                    <?php echo form_close(); ?>

                </div>
                <div class="clearfix"></div>


                <div class="table-responsive">
                    <table id="SalesGST" class="table table-bordered table-hover table-striped table-condensed reports-table">

                        <thead>
                            <tr>
                                <th><?= lang("GST Rate"); ?></th>
                                <th><?= lang("Taxable Amt "); ?></th>
                                <th><?= lang("SGST"); ?></th>
                                <th><?= lang("CGST"); ?></th>
                                <th><?= lang("IGST"); ?></th>
                                <th><?= lang("Total GST"); ?></th>
                                <th><?= lang("Sales Amt");?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="7" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
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
                            <th></th>
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
            window.location.href = "<?=site_url('reports/getTaxReports/pdf/?v=1'.$v)?>";
            return false;
        });
        $('#xls').click(function (event) {
            event.preventDefault();
            window.location.href = "<?=site_url('reports/getTaxReports/0/xls/?v=1'.$v)?>";
            return false;
        });
    });
    $(document).ready(function () {
        $('#form').hide();
       
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