<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller {

    function __construct() {
        parent::__construct();
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 3000);

        if (!$this->loggedIn) {
            $this->session->set_userdata('requested_page', $this->uri->uri_string());
            $this->sma->md('login');
        }

        $this->lang->load('reports', $this->Settings->user_language);
        $this->load->library('form_validation');
        $this->load->model('reports_model');
        $this->data['pb'] = array('cash' => lang('cash'), 'CC' => lang('CC'), 'Cheque' => lang('Cheque'), 'paypal_pro' => lang('paypal_pro'), 'stripe' => lang('stripe'), 'gift_card' => lang('gift_card'), 'deposit' => lang('deposit'), 'authorize' => lang('authorize'),);
    }

    function index() {
        $this->sma->checkPermissions();
        $data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['monthly_sales'] = $this->reports_model->getChartData();
        $this->data['stock'] = $this->reports_model->getStockValue();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('reports')));
        $meta = array('page_title' => lang('reports'), 'bc' => $bc);
        $this->page_construct('reports/index', $meta, $this->data);
    }

    function warehouse_stock($warehouse = NULL) {
        $this->sma->checkPermissions('index', TRUE);
        $data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        if ($this->input->get('warehouse')) {
            $warehouse = $this->input->get('warehouse');
        }

        $this->data['stock'] = $warehouse ? $this->reports_model->getWarehouseStockValue($warehouse) : $this->reports_model->getStockValue();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse_id'] = $warehouse;
        $this->data['warehouse'] = $warehouse ? $this->site->getWarehouseByID($warehouse) : NULL;
        $this->data['totals'] = $this->reports_model->getWarehouseTotals($warehouse);
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('reports')));
        $meta = array('page_title' => lang('reports'), 'bc' => $bc);
        $this->page_construct('reports/warehouse_stock', $meta, $this->data);
    }

    function expiry_alerts($warehouse_id = NULL) {
        $this->sma->checkPermissions('expiry_alerts');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        if ($this->Owner || $this->Admin || !$this->session->userdata('warehouse_id')) {
            $this->data['warehouses'] = $this->site->getAllWarehouses();
            $this->data['warehouse_id'] = $warehouse_id;
            $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        } else {
            $user = $this->site->getUser();
            $this->data['warehouses'] = $this->site->getAllWarehouses();
            $this->data['warehouse_id'] = ($warehouse_id == NULL) ? $this->session->userdata('warehouse_id') : $warehouse_id;

            $this->data['warehouse'] = $user->warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        }

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('product_expiry_alerts')));
        $meta = array('page_title' => lang('product_expiry_alerts'), 'bc' => $bc);
        $this->page_construct('reports/expiry_alerts', $meta, $this->data);
    }

    function getExpiryAlerts($warehouse_id = NULL, $pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('expiry_alerts', TRUE);
        $date = date('Y-m-d', strtotime('+3 months'));

        if (!$this->Owner && !$warehouse_id) {
            $user = $this->site->getUser();
            $warehouse_id = $user->warehouse_id;
        }
        if ($pdf || $xls || $img) {
            if ($warehouse_id) {
                $getwarehouse = str_replace("_", ",", $warehouse_id);
                $this->db->select("image, product_code, product_name, product_variants.name as variants, quantity_balance, warehouses.name, expiry, batch_number")->from('purchase_items')->join('products', 'products.id=purchase_items.product_id', 'left')->join('warehouses', 'warehouses.id=purchase_items.warehouse_id', 'left ')->join('product_variants', 'product_variants.id=purchase_items.option_id', 'left ')->where('warehouse_id IN(' . $getwarehouse . ')')->where('expiry !=', NULL)->where('expiry !=', '0000-00-00')->where('expiry <', $date);
            } else {
                $this->db->select("image, product_code, product_name, product_variants.name as variants, quantity_balance, warehouses.name, expiry, batch_number")->from('purchase_items')->join('products', 'products.id=purchase_items.product_id', 'left')->join('warehouses', 'warehouses.id=purchase_items.warehouse_id', 'left')->join('product_variants', 'product_variants.id=purchase_items.option_id', 'left ')->where('expiry !=', NULL)->where('expiry !=', '0000-00-00')->where('expiry <', $date);
            }

            $q = $this->db->get();

            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }
            // print_r($data);
            // exit;
            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:G1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:G1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Product Expiry Alerts (All Warehouses)');

                $this->excel->getActiveSheet()->setTitle(lang('product_expiry_alerts'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('variants'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('warehouse'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('expiry'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('batch_number'));


                $row = 3;

                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->product_code);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->product_name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->variants);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->quantity_balance);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->expiry);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->batch_number);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);


                $filename = 'product_expiry_alerts';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getDefaultStyle('A1')->getAlignment()->applyFromArray(
                            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,)
                    );
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getDefaultStyle('A1')->getAlignment()->applyFromArray(
                            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,)
                    );
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/product_expiry_alerts.pdf', __FILE__));
                    redirect("reports/create_image/product_expiry_alerts.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            $this->load->library('datatables');
            if ($warehouse_id) {
                $getwarehouse = str_replace("_", ",", $warehouse_id);
                $this->datatables->select("image, product_code, product_name, product_variants.name as variants, quantity_balance, warehouses.name, expiry, batch_number")->from('purchase_items')->join('products', 'products.id=purchase_items.product_id', 'left')->join('warehouses', 'warehouses.id=purchase_items.warehouse_id', 'left ')->join('product_variants', 'product_variants.id=purchase_items.option_id', 'left ')->where('warehouse_id IN(' . $getwarehouse . ')')->where('expiry !=', NULL)->where('expiry !=', '0000-00-00')->where('expiry <', $date);
            } else {
                $this->datatables->select("image, product_code, product_name, product_variants.name as variants, quantity_balance, warehouses.name, expiry, batch_number")->from('purchase_items')->join('products', 'products.id=purchase_items.product_id', 'left')->join('warehouses', 'warehouses.id=purchase_items.warehouse_id', 'left ')->join('product_variants', 'product_variants.id=purchase_items.option_id', 'left ')->where('expiry !=', NULL)->where('expiry !=', '0000-00-00')->where('expiry <', $date);
            }
            echo $this->datatables->generate();
        }
    }

    function quantity_alerts($warehouse_id = NULL) {
        $this->sma->checkPermissions('quantity_alerts');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        if ($this->Owner || $this->Admin || !$this->session->userdata('warehouse_id')) {
            $this->data['warehouses'] = $this->site->getAllWarehouses();
            $this->data['warehouse_id'] = $warehouse_id;
            $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        } else {
            $user = $this->site->getUser();
            $this->data['warehouses'] = $this->site->getAllWarehouses();
            $this->data['warehouse_id'] = ($warehouse_id == NULL) ? $user->warehouse_id : $warehouse_id;
            $this->data['warehouse'] = $user->warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        }

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('product_quantity_alerts')));
        $meta = array('page_title' => lang('product_quantity_alerts'), 'bc' => $bc);
        $this->page_construct('reports/quantity_alerts', $meta, $this->data);
    }

    function getQuantityAlerts($warehouse_id = NULL, $pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('quantity_alerts', TRUE);
        if (!$this->Owner && !$warehouse_id) {
            $user = $this->site->getUser();
            $warehouse_id = $user->warehouse_id;
        }

        if ($pdf || $xls || $img) {

            if ($warehouse_id) {
                $getwarehouse = str_replace("_", ",", $warehouse_id);
                $this->db->select('products.image as image, products.code, products.name, warehouses_products.quantity, alert_quantity')->from('products')->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')->where('alert_quantity > warehouses_products.quantity', NULL)->where('warehouse_id IN (' . $getwarehouse . ')')->where('track_quantity', 1)->order_by('products.code desc');
            } else {
                //$this->db->select('image, code, name, quantity, alert_quantity')->from('products')->where('alert_quantity > quantity', NULL)->where('track_quantity', 1)->order_by('code desc');

                $this->db->select('image, code, name,  warehouses_products.quantity, alert_quantity')->from('products')->join('warehouses_products', 'products.id = warehouses_products.product_id', 'left')->where('alert_quantity > warehouses_products.quantity', NULL)->or_where('warehouses_products.quantity', NULL)->where('track_quantity', 1); //->group_by('products.id')
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:D1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:D1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Product Quantity Alerts (All Warehouses)');
                $this->excel->getActiveSheet()->setTitle(lang('product_quantity_alerts'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('alert_quantity'));

                $row = 3;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->code);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->quantity);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->alert_quantity);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);

                $filename = 'product_quantity_alerts';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/product_quantity_alerts.pdf', __FILE__));
                    redirect("reports/create_image/product_quantity_alerts.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            $this->load->library('datatables');
            if ($warehouse_id) {
                $getwarehouse = str_replace("_", ",", $warehouse_id);
                $where = '(alert_quantity > wp.quantity  or wp.quantity IS NULL)';
                /* $this->datatables->select('image, code, name, wp.quantity, alert_quantity')->from('products')->join("( SELECT * from {$this->db->dbprefix('warehouses_products')} WHERE warehouse_id IN ({$getwarehouse})) wp", 'products.id=wp.product_id', 'left')->where('alert_quantity > wp.quantity', NULL)->or_where('wp.quantity', NULL)->where('track_quantity', 1)->group_by('products.id'); */

                $this->datatables->select('image, code, name, wp.quantity, alert_quantity')->from('products')->join("( SELECT * from {$this->db->dbprefix('warehouses_products')} WHERE warehouse_id IN ({$getwarehouse})) wp", 'products.id=wp.product_id', 'left')->where($where)->where('track_quantity', 1)->group_by('products.id');
            } else {
                //$this->datatables->select('image, code, name, quantity, alert_quantity')->from('products')->where('alert_quantity > quantity', NULL)->where('track_quantity', 1);

                $this->datatables->select('image, code, name,  wp.quantity, alert_quantity')->from('products')->join("( SELECT * from {$this->db->dbprefix('warehouses_products')}) wp", 'products.id = wp.product_id', 'left')->where('alert_quantity > wp.quantity', NULL)->or_where('wp.quantity', NULL)->where('track_quantity', 1); //->group_by('products.id')
            }

            echo $this->datatables->generate();
        }
    }

    function suggestions() {
        $term = $this->input->get('term', TRUE);
        if (strlen($term) < 1) {
            die();
        }

        $rows = $this->reports_model->getProductNames($term);
        if ($rows) {
            foreach ($rows as $row) {
                $pr[] = array('id' => $row->id, 'label' => $row->name . " (" . $row->code . ")");
            }
            $this->sma->send_json($pr);
        } else {
            echo FALSE;
        }
    }

    public function best_sellers($warehouse_id = 0, $yr = null, $mmt = null) {
        $monthget = null;
        $wareget = $this->data['wareget'] = $this->uri->segment(3);
        $yearget = $this->data['yearget'] = $this->uri->segment(4);
        $monthget = $this->data['monthget'] = $this->uri->segment(5);

        $warehouse_id = $this->uri->segment(3);

        $this->sma->checkPermissions('products');

        $mt = ($mmt) ? $mmt + 1 : NULL;
        $mtla = ($mmt != 12) ? $mmt + 1 : 1; //use for last graph show 6/2/2020
        $y = date('Y');
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $y1 = ($yr) ? ($mt > 1 ? $yr : ( $yr - 1 ) ) : date('Y', strtotime('-1 month'));

        $m1 = ($mt) ? ($mt > 1 ? $mt - 1 : (12 + $mt) - 1 ) : date('m', strtotime('-1 month'));

        $month = $this->data['months'] = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec');
        $year = $this->data['years'] = array(1 => $y, 2 => ($y - 1));
        $m1sdate = $y1 . '-' . $m1 . '-01 00:00:00';
        $m1edate = $y1 . '-' . $m1 . '-' . days_in_month($m1, $y1) . ' 23:59:59';
        $this->data['m1'] = date('M Y', strtotime($y1 . '-' . $m1));
        $this->data['m1bs'] = $this->reports_model->getBestSeller($m1sdate, $m1edate, $warehouse_id, $monthget);
        $y2 = ($yr) ? ($mt > 2 ? $yr : ( $yr - 1 ) ) : date('Y', strtotime('-2 months'));
        $m2 = ($mt) ? ($mt > 2 ? $mt - 2 : (12 + $mt) - 2 ) : date('m', strtotime('-2 months'));
        $m2sdate = $y2 . '-' . $m2 . '-01 00:00:00';
        $m2edate = $y2 . '-' . $m2 . '-' . days_in_month($m2, $y2) . ' 23:59:59';
        $this->data['m2'] = date('M Y', strtotime($y2 . '-' . $m2));
        $this->data['m2bs'] = $this->reports_model->getBestSeller($m2sdate, $m2edate, $warehouse_id, $monthget);
        $y3 = ($yr) ? ($mt > 3 ? $yr : ( $yr - 1)) : date('Y', strtotime('-3 months'));
        $m3 = ($mt) ? ($mt > 3 ? $mt - 3 : (12 + $mt) - 3 ) : date('m', strtotime('-3 months'));
        $m3sdate = $y3 . '-' . $m3 . '-01 23:59:59';
        $this->data['m3'] = date('M Y', strtotime($y3 . '-' . $m3)) . ' - ' . $this->data['m1'];
        $this->data['m3bs'] = $this->reports_model->getBestSeller($m3sdate, $m1edate, $warehouse_id, $monthget);
        $y4 = ($yr) ? $yr - 1 : date('Y', strtotime('-12 months'));
        $m4 = ($mtla) ? $mtla : date('m', strtotime('-12 months'));
        $m4sdate = $y4 . '-' . $m4 . '-01 23:59:59';
        $this->data['m4'] = date('M Y', strtotime($y4 . '-' . $m4)) . ' - ' . $this->data['m1'];
        $this->data['m4bs'] = $this->reports_model->getBestSeller($m4sdate, $m1edate, $warehouse_id, $monthget);
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('best_sellers')));
        $meta = array('page_title' => lang('best_sellers'), 'bc' => $bc);
        $this->page_construct('reports/best_sellers', $meta, $this->data);
    }

    function products() {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->data['brands'] = $this->site->getAllBrands();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_report')));
        $meta = array('page_title' => lang('products_report'), 'bc' => $bc);
        $this->page_construct('reports/products', $meta, $this->data);
    }

    function getProductsReport($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('products', TRUE);

        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $category = $this->input->get('category') ? $this->input->get('category') : NULL;
        $brand = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $subcategory = $this->input->get('subcategory') ? $this->input->get('subcategory') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $cf1 = $this->input->get('cf1') ? $this->input->get('cf1') : NULL;
        $cf2 = $this->input->get('cf2') ? $this->input->get('cf2') : NULL;
        $cf3 = $this->input->get('cf3') ? $this->input->get('cf3') : NULL;
        $cf4 = $this->input->get('cf4') ? $this->input->get('cf4') : NULL;
        $cf5 = $this->input->get('cf5') ? $this->input->get('cf5') : NULL;
        $cf6 = $this->input->get('cf6') ? $this->input->get('cf6') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $with_or_without_gst = $this->input->get('with_or_without_gst') ? $this->input->get('with_or_without_gst') : NULL;
//echo $with_or_without_gst;exit;
        if ($with_or_without_gst == 'without_gst') {
            $pp = "( SELECT product_id, SUM(CASE WHEN pi.purchase_id IS NOT NULL THEN quantity ELSE 0 END) as purchasedQty,
        SUM(quantity_balance) as balacneQty,
        SUM( net_unit_cost * quantity_balance ) balacneValue,
        SUM( (CASE WHEN pi.purchase_id IS NOT NULL THEN (pi.subtotal - pi.item_tax) ELSE 0 END) ) totalPurchase from {$this->db->dbprefix('purchase_items')} pi LEFT JOIN {$this->db->dbprefix('purchases')} p on p.id = pi.purchase_id ";
            $sp = "( SELECT si.product_id, SUM( si.quantity ) soldQty, SUM( si.subtotal - si.item_tax ) totalSale from " . $this->db->dbprefix('sales') . " s JOIN " . $this->db->dbprefix('sale_items') . " si on s.id = si.sale_id ";
        } else {
            $pp = "( SELECT product_id, SUM(CASE WHEN pi.purchase_id IS NOT NULL THEN quantity ELSE 0 END) as purchasedQty,
        SUM(quantity_balance) as balacneQty,
        SUM( unit_cost * quantity_balance ) balacneValue,
        SUM( (CASE WHEN pi.purchase_id IS NOT NULL THEN (pi.subtotal) ELSE 0 END) ) totalPurchase from {$this->db->dbprefix('purchase_items')} pi LEFT JOIN {$this->db->dbprefix('purchases')} p on p.id = pi.purchase_id ";
            $sp = "( SELECT si.product_id, SUM( si.quantity ) soldQty, SUM( si.subtotal ) totalSale from " . $this->db->dbprefix('sales') . " s JOIN " . $this->db->dbprefix('sale_items') . " si on s.id = si.sale_id ";
        }
        if ($start_date || $warehouse) {
            $pp .= " WHERE ";
            $sp .= " WHERE ";
            if ($start_date) {
                $start_date = $this->sma->fld($start_date);
                $end_date = $end_date ? $this->sma->fld($end_date) : date('Y-m-d');
                $pp .= " (DATE(p.date) BETWEEN '{$start_date}' AND '{$end_date}') ";
                $sp .= " (DATE(s.date) BETWEEN'{$start_date}' AND '{$end_date}') ";
                if ($warehouse) {
                    $pp .= " AND ";
                    $sp .= " AND ";
                }
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $pp .= " pi.warehouse_id IN ({$getwarehouse}) ";
                $sp .= " si.warehouse_id IN  ({$getwarehouse}) ";

                if ($this->session->userdata('view_right') == '0') {
                    $pp .= " AND p.created_by =" . $this->session->userdata('user_id');
                    $sp .= "AND s.created_by  =  " . $this->session->userdata('user_id');
                }
            }
        }
        $pp .= " GROUP BY pi.product_id ) PCosts";
        $sp .= " GROUP BY si.product_id ) PSales";
        if ($pdf || $xls || $img) {

            $this->db->select($this->db->dbprefix('products') . ".code, " . $this->db->dbprefix('products') . ".name,
				COALESCE( PCosts.purchasedQty, 0 ) as PurchasedQty,
				COALESCE( PSales.soldQty, 0 ) as SoldQty,
				COALESCE( PCosts.balacneQty, 0 ) as BalacneQty,
				COALESCE( PCosts.totalPurchase, 0 ) as TotalPurchase,
				COALESCE( PCosts.balacneValue, 0 ) as TotalBalance,
				COALESCE( PSales.totalSale, 0 ) as TotalSales,
                (COALESCE( PSales.totalSale, 0 ) - COALESCE( PCosts.totalPurchase, 0 )) as Profit", FALSE)->from('products')->join($sp, 'products.id = PSales.product_id', 'left')->join($pp, 'products.id = PCosts.product_id', 'left');//->order_by('products.name')

            if ($this->data['Settings']->display_zero_sale_for_product_report == 0)
                $this->db->where('soldQty !=', '0');
                $this->db->group_by('products.code, PSales.soldQty, PSales.totalSale, PCosts.purchasedQty, PCosts.totalPurchase, PCosts.balacneQty, PCosts.balacneValue');

            if ($product) {
                $this->db->where($this->db->dbprefix('products') . ".id", $product);
            }
            if ($cf1) {
                $this->db->where($this->db->dbprefix('products') . ".cf1", $cf1);
            }
            if ($cf2) {
                $this->db->where($this->db->dbprefix('products') . ".cf2", $cf2);
            }
            if ($cf3) {
                $this->db->where($this->db->dbprefix('products') . ".cf3", $cf3);
            }
            if ($cf4) {
                $this->db->where($this->db->dbprefix('products') . ".cf4", $cf4);
            }
            if ($cf5) {
                $this->db->where($this->db->dbprefix('products') . ".cf5", $cf5);
            }
            if ($cf6) {
                $this->db->where($this->db->dbprefix('products') . ".cf6", $cf6);
            }
            if ($category) {
                $this->db->where($this->db->dbprefix('products') . ".category_id", $category);
            }
            if ($subcategory) {
                $this->db->where($this->db->dbprefix('products') . ".subcategory_id", $subcategory);
            }
            if ($brand) {
                $this->db->where($this->db->dbprefix('products') . ".brand", $brand);
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:I1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Report');
                $this->excel->getActiveSheet()->setTitle(lang('products_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('purchased'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('sold'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('balance'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('purchased_amount'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('sold_amount'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('profit_loss'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('stock_in_hand'));

                $row = 3;
                $sQty = 0;
                $pQty = 0;
                $sAmt = 0;
                $pAmt = 0;
                $bQty = 0;
                $bAmt = 0;
                $pl = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->code);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->PurchasedQty);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->SoldQty);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->BalacneQty);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->TotalPurchase);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->TotalSales);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->Profit);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->TotalBalance);
                    $pQty += $data_row->PurchasedQty;
                    $sQty += $data_row->SoldQty;
                    $bQty += $data_row->BalacneQty;
                    $pAmt += $data_row->TotalPurchase;
                    $sAmt += $data_row->TotalSales;
                    $bAmt += $data_row->TotalBalance;
                    $pl += $data_row->Profit;
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("C" . $row . ":I" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $pQty);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $sQty);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $bQty);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $pAmt);
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $sAmt);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $pl);
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $bAmt);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(25);

                $filename = 'products_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    $this->excel->getActiveSheet()->getStyle('C2:G' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_report.pdf', __FILE__));
                    redirect("reports/create_image/products_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
           /* $this->datatables->select($this->db->dbprefix('products') . ".code, " . $this->db->dbprefix('products') . ".name,
				CONCAT(COALESCE( PCosts.purchasedQty, 0 ), '__', COALESCE( PCosts.totalPurchase, 0 )) as purchased,
				CONCAT(COALESCE( PSales.soldQty, 0 ), '__', COALESCE( PSales.totalSale, 0 )) as sold,
                (COALESCE( PSales.totalSale, 0 ) - COALESCE( PCosts.totalPurchase, 0 )) as Profit,
				CONCAT(COALESCE( PCosts.balacneQty, 0 ), '__', COALESCE( PCosts.balacneValue, 0 )) as balance,
				{$this->db->dbprefix('products')}.id as id", FALSE) */
             $this->datatables->select($this->db->dbprefix('products') . ".code, " . $this->db->dbprefix('products') . ".name,
				IF(PCosts.purchasedQty,round(PCosts.purchasedQty,2) ,0) as purchase_qty , PCosts.totalPurchase as purchased,
				 IF(PSales.soldQty, round(PSales.soldQty,2),0) as sold_qty, PSales.totalSale as sold,
                (COALESCE( PSales.totalSale, 0 ) - COALESCE( PCosts.totalPurchase, 0 )) as Profit,
				 IF(PCosts.balacneQty,round(PCosts.balacneQty,2),0) as balance_qty , PCosts.balacneValue as balance,
				{$this->db->dbprefix('products')}.id as id", FALSE)  
                    ->from('products')
                    ->join($sp, 'products.id = PSales.product_id', 'left')
                    ->join($pp, 'products.id = PCosts.product_id', 'left');
            if ($this->data['Settings']->display_zero_sale_for_product_report == 0)
                $this->datatables->where('soldQty !=', '0');
            $this->datatables->group_by('products.code, PSales.soldQty, PSales.totalSale, PCosts.purchasedQty, PCosts.totalPurchase, PCosts.balacneQty, PCosts.balacneValue');

            if ($product) {
                $this->datatables->where($this->db->dbprefix('products') . ".id", $product);
            }
            if ($cf1) {
                $this->datatables->where($this->db->dbprefix('products') . ".cf1", $cf1);
            }
            if ($cf2) {
                $this->datatables->where($this->db->dbprefix('products') . ".cf2", $cf2);
            }
            if ($cf3) {
                $this->datatables->where($this->db->dbprefix('products') . ".cf3", $cf3);
            }
            if ($cf4) {
                $this->datatables->where($this->db->dbprefix('products') . ".cf4", $cf4);
            }
            if ($cf5) {
                $this->datatables->where($this->db->dbprefix('products') . ".cf5", $cf5);
            }
            if ($cf6) {
                $this->datatables->where($this->db->dbprefix('products') . ".cf6", $cf6);
            }
            if ($category) {
                $this->datatables->where($this->db->dbprefix('products') . ".category_id", $category);
            }
            if ($subcategory) {
                $this->datatables->where($this->db->dbprefix('products') . ".subcategory_id", $subcategory);
            }
            if ($brand) {
                $this->datatables->where($this->db->dbprefix('products') . ".brand", $brand);
            }

            echo $this->datatables->generate();
        }
    }

    function categories() {
        $this->sma->checkPermissions('products');
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('categories_report')));
        $meta = array('page_title' => lang('categories_report'), 'bc' => $bc);
        $this->page_construct('reports/categories', $meta, $this->data);
    }

    function getCategoriesReport($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('products', TRUE);
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $category = $this->input->get('category') ? $this->input->get('category') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $pp = "( SELECT pp.category_id as category, SUM( pi.quantity ) purchasedQty, SUM( pi.subtotal ) totalPurchase from {$this->db->dbprefix('products')} pp
                left JOIN " . $this->db->dbprefix('purchase_items') . " pi ON pp.id = pi.product_id
                left join " . $this->db->dbprefix('purchases') . " p ON p.id = pi.purchase_id ";
        $sp = "( SELECT sp.category_id as category, SUM( si.quantity ) soldQty, SUM( si.subtotal ) totalSale from {$this->db->dbprefix('products')} sp
                left JOIN " . $this->db->dbprefix('sale_items') . " si ON sp.id = si.product_id
                left join " . $this->db->dbprefix('sales') . " s ON s.id = si.sale_id ";
        if ($start_date || $warehouse) {
            $pp .= " WHERE ";
            $sp .= " WHERE ";
            if ($start_date) {
                $start_date = $this->sma->fld($start_date);
                $end_date = $end_date ? $this->sma->fld($end_date) : date('Y-m-d');
                $pp .= " p.date >= '{$start_date}' AND p.date < '{$end_date}' ";
                $sp .= " s.date >= '{$start_date}' AND s.date < '{$end_date}' ";
                if ($warehouse) {
                    $pp .= " AND ";
                    $sp .= " AND ";
                }
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $pp .= " pi.warehouse_id IN({$getwarehouse}) ";
                $sp .= " si.warehouse_id IN({$getwarehouse}) ";

                if ($this->session->userdata('view_right') == '0') {
                    $pp .= " AND p.created_by =" . $this->session->userdata('user_id');
                    $sp .= "AND s.created_by  =  " . $this->session->userdata('user_id');
                }
            }
        }
        $pp .= " GROUP BY pp.category_id ) PCosts";
        $sp .= " GROUP BY sp.category_id ) PSales";

        if ($pdf || $xls || $img) {

            $this->db->select($this->db->dbprefix('categories') . ".code, " . $this->db->dbprefix('categories') . ".name,
                    SUM( COALESCE( PCosts.purchasedQty, 0 ) ) as PurchasedQty,
                    SUM( COALESCE( PSales.soldQty, 0 ) ) as SoldQty,
                    SUM( COALESCE( PCosts.totalPurchase, 0 ) ) as TotalPurchase,
                    SUM( COALESCE( PSales.totalSale, 0 ) ) as TotalSales,
                    (SUM( COALESCE( PSales.totalSale, 0 ) )- SUM( COALESCE( PCosts.totalPurchase, 0 ) ) ) as Profit", FALSE)->from('categories')->join($sp, 'categories.id = PSales.category', 'left')->join($pp, 'categories.id = PCosts.category', 'left')->group_by('categories.id, categories.code, categories.name')->order_by('categories.code', 'asc');

            if ($category) {
                $this->db->where($this->db->dbprefix('categories') . ".id", $category);
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:G1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:G1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Categories Report');
                $this->excel->getActiveSheet()->setTitle(lang('categories_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('category_code'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('category_name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('purchased'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('sold'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('purchased_amount'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('sold_amount'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('profit_loss'));

                $row = 3;
                $sQty = 0;
                $pQty = 0;
                $sAmt = 0;
                $pAmt = 0;
                $pl = 0;
                foreach ($data as $data_row) {
                    $profit = $data_row->TotalSales - $data_row->TotalPurchase;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->code);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->PurchasedQty);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->SoldQty);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->TotalPurchase);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->TotalSales);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $profit);
                    $pQty += $data_row->PurchasedQty;
                    $sQty += $data_row->SoldQty;
                    $pAmt += $data_row->TotalPurchase;
                    $sAmt += $data_row->TotalSales;
                    $pl += $profit;
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("C" . $row . ":G" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $pQty);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $sQty);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $pAmt);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $sAmt);
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $pl);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);

                $filename = 'categories_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));

                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getDefaultStyle('A1')->getAlignment()->applyFromArray(
                            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,)
                    );
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    $this->excel->getActiveSheet()->getStyle('C2:G' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));

                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getDefaultStyle('A1')->getAlignment()->applyFromArray(
                            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,)
                    );
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/categories_report.pdf', __FILE__));
                    redirect("reports/create_image/categories_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {


            $this->load->library('datatables');
            $this->datatables->select($this->db->dbprefix('categories') . ".id as cid, " . $this->db->dbprefix('categories') . ".code, " . $this->db->dbprefix('categories') . ".name,
                    SUM( COALESCE( PCosts.purchasedQty, 0 ) ) as PurchasedQty,
                    SUM( COALESCE( PSales.soldQty, 0 ) ) as SoldQty,
                    SUM( COALESCE( PCosts.totalPurchase, 0 ) ) as TotalPurchase,
                    SUM( COALESCE( PSales.totalSale, 0 ) ) as TotalSales,
                    (SUM( COALESCE( PSales.totalSale, 0 ) )- SUM( COALESCE( PCosts.totalPurchase, 0 ) ) ) as Profit", FALSE)->from('categories')->join($sp, 'categories.id = PSales.category', 'left')->join($pp, 'categories.id = PCosts.category', 'left');

            if ($category) {
                $this->datatables->where('categories.id', $category);
            }
            $this->datatables->group_by('categories.id, categories.code, categories.name, PSales.SoldQty, PSales.totalSale, PCosts.purchasedQty, PCosts.totalPurchase');
            $this->datatables->unset_column('cid');
            echo $this->datatables->generate();
        }
    }

    function brands() {
        $this->sma->checkPermissions('products');
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['brands'] = $this->site->getAllBrands();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('brands_report')));
        $meta = array('page_title' => lang('brands_report'), 'bc' => $bc);
        $this->page_construct('reports/brands', $meta, $this->data);
    }

    function getBrandsReport($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('products', TRUE);
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $brand = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $pp = "( SELECT pp.brand as brand, SUM( pi.quantity ) purchasedQty, SUM( pi.subtotal ) totalPurchase from {$this->db->dbprefix('products')} pp
                left JOIN " . $this->db->dbprefix('purchase_items') . " pi ON pp.id = pi.product_id
                left join " . $this->db->dbprefix('purchases') . " p ON p.id = pi.purchase_id ";
        $sp = "( SELECT sp.brand as brand, SUM( si.quantity ) soldQty, SUM( si.subtotal ) totalSale from {$this->db->dbprefix('products')} sp
                left JOIN " . $this->db->dbprefix('sale_items') . " si ON sp.id = si.product_id
                left join " . $this->db->dbprefix('sales') . " s ON s.id = si.sale_id ";
        if ($start_date || $warehouse) {
            $pp .= " WHERE ";
            $sp .= " WHERE ";
            if ($start_date) {
                $start_date = $this->sma->fld($start_date);
                $end_date = $end_date ? $this->sma->fld($end_date) : date('Y-m-d');
                $pp .= " (DATE(p.date) between '{$start_date}' AND  '{$end_date}') ";
                $sp .= " (DATE(s.date) between '{$start_date}' AND  '{$end_date}') ";
                if ($warehouse) {
                    $pp .= " AND ";
                    $sp .= " AND ";
                }
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $pp .= " pi.warehouse_id IN ({$getwarehouse}) ";
                $sp .= " si.warehouse_id IN ({$getwarehouse}) ";

                if ($this->session->userdata('view_right') == '0') {
                    $pp .= " AND p.created_by =" . $this->session->userdata('user_id');
                    $sp .= "AND s.created_by  =  " . $this->session->userdata('user_id');
                }
            }
        }
        $pp .= " GROUP BY pp.brand ) PCosts";
        $sp .= " GROUP BY sp.brand ) PSales";

        if ($pdf || $xls || $img) {

            $this->db->select($this->db->dbprefix('brands') . ".name,
                    SUM( COALESCE( PCosts.purchasedQty, 0 ) ) as PurchasedQty,
                    SUM( COALESCE( PSales.soldQty, 0 ) ) as SoldQty,
                    SUM( COALESCE( PCosts.totalPurchase, 0 ) ) as TotalPurchase,
                    SUM( COALESCE( PSales.totalSale, 0 ) ) as TotalSales,
                    (SUM( COALESCE( PSales.totalSale, 0 ) )- SUM( COALESCE( PCosts.totalPurchase, 0 ) ) ) as Profit", FALSE)->from('brands')->join($sp, 'brands.id = PSales.brand', 'left')->join($pp, 'brands.id = PCosts.brand', 'left')->group_by('brands.id, brands.name')->order_by('brands.code', 'asc');

            if ($brand) {
                $this->db->where($this->db->dbprefix('brands') . ".id", $brand);
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:F1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:F1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Brands Report');
                $this->excel->getActiveSheet()->setTitle(lang('brands_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('brands'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('purchased'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('sold'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('purchased_amount'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('sold_amount'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('profit_loss'));

                $row = 3;
                $sQty = 0;
                $pQty = 0;
                $sAmt = 0;
                $pAmt = 0;
                $pl = 0;
                foreach ($data as $data_row) {
                    $profit = $data_row->TotalSales - $data_row->TotalPurchase;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->PurchasedQty);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->SoldQty);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->TotalPurchase);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->TotalSales);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $profit);
                    $pQty += $data_row->PurchasedQty;
                    $sQty += $data_row->SoldQty;
                    $pAmt += $data_row->TotalPurchase;
                    $sAmt += $data_row->TotalSales;
                    $pl += $profit;
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("B" . $row . ":F" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $pQty);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $sQty);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $pAmt);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $sAmt);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $pl);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);

                $filename = 'brands_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    $this->excel->getActiveSheet()->getStyle('C2:G' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/brands_report.pdf', __FILE__));
                    redirect("reports/create_image/brands_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {


            $this->load->library('datatables');
            $this->datatables->select($this->db->dbprefix('brands') . ".id as id, " . $this->db->dbprefix('brands') . ".name,
                    SUM( COALESCE( PCosts.purchasedQty, 0 ) ) as PurchasedQty,
                    SUM( COALESCE( PSales.soldQty, 0 ) ) as SoldQty,
                    SUM( COALESCE( PCosts.totalPurchase, 0 ) ) as TotalPurchase,
                    SUM( COALESCE( PSales.totalSale, 0 ) ) as TotalSales,
                    (SUM( COALESCE( PSales.totalSale, 0 ) )- SUM( COALESCE( PCosts.totalPurchase, 0 ) ) ) as Profit", FALSE)->from('brands')->join($sp, 'brands.id = PSales.brand', 'left')->join($pp, 'brands.id = PCosts.brand', 'left');

            if ($brand) {
                $this->datatables->where('brands.id', $brand);
            }
            $this->datatables->group_by('brands.id, brands.name, PSales.SoldQty, PSales.totalSale, PCosts.purchasedQty, PCosts.totalPurchase');
            $this->datatables->unset_column('id');
            echo $this->datatables->generate();
        }
    }

    function profit($date = NULL, $warehouse_id = NULL) {
        if (!$this->Owner) {
            $this->session->set_flashdata('error', lang('access_denied'));
            $this->sma->md();
        }
        if (!$date) {
            $date = date('Y-m-d');
        }
        $this->data['costing'] = $this->reports_model->getCosting($date, $warehouse_id);
        $this->data['discount'] = $this->reports_model->getOrderDiscount($date, $warehouse_id);
        $this->data['expenses'] = $this->reports_model->getExpenses($date, $warehouse_id);
        $this->data['returns'] = $this->reports_model->getReturns($date, $warehouse_id);
        $this->data['date'] = $date;
        $this->load->view($this->theme . 'reports/profit', $this->data);
    }

    function monthly_profit($year, $month, $warehouse_id = NULL) {
        if (!$this->Owner) {
            $this->session->set_flashdata('error', lang('access_denied'));
            $this->sma->md();
        }

        $this->data['costing'] = $this->reports_model->getCosting(NULL, $warehouse_id, $year, $month);
        $this->data['discount'] = $this->reports_model->getOrderDiscount(NULL, $warehouse_id, $year, $month);
        $this->data['expenses'] = $this->reports_model->getExpenses(NULL, $warehouse_id, $year, $month);
        $this->data['returns'] = $this->reports_model->getReturns(NULL, $warehouse_id, $year, $month);
        $this->data['date'] = date('F Y', strtotime($year . '-' . $month . '-' . '01'));
        $this->load->view($this->theme . 'reports/monthly_profit', $this->data);
    }

    function daily_sales($warehouse_id = NULL, $year = NULL, $month = NULL, $pdf = NULL, $user_id = NULL) {
        $this->sma->checkPermissions();
        if ($warehouse_id != NULL) {
            $warehouse_id = $warehouse_id;
        } else if (!$this->Owner && !$this->Admin && $this->session->userdata('warehouse_id')) {
            $warehouse_id = str_replace(",", "_", $this->session->userdata('warehouse_id'));
        }

        $this->data['sel_warehouse'] = $warehouse_id ? (strpos($warehouse_id, '_') !== false) ? NULL : $this->site->getWarehouseByID($warehouse_id) : NULL;


        if (!$year) {
            $year = date('Y');
        }
        if (!$month) {
            $month = date('m');
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $key = $this->data['sel_warehouse'] ? array_keys($this->data['sel_warehouse']) : 0; //Use to get Id on sel_warehouse 
        //$config = array('show_next_prev' => TRUE, 'next_prev_url' => site_url('reports/daily_sales/' . ($this->data['sel_warehouse']? $this->data['sel_warehouse']->id : 0)), 'month_type' => 'long', 'day_type' => 'long');

        $config = array('show_next_prev' => TRUE, 'next_prev_url' => site_url('reports/daily_sales/' . ($this->data['sel_warehouse'] ? $key[0] : 0)), 'month_type' => 'long', 'day_type' => 'long');

        $config['template'] = '{table_open}<div class="table-responsive"><table border="0" cellpadding="0" cellspacing="0" class="table table-bordered dfTable">{/table_open}
		{heading_row_start}<tr>{/heading_row_start}
		{heading_previous_cell}<th><a href="{previous_url}">&lt;&lt;</a></th>{/heading_previous_cell}
		{heading_title_cell}<th colspan="{colspan}" id="month_year">{heading}</th>{/heading_title_cell}
		{heading_next_cell}<th><a href="{next_url}">&gt;&gt;</a></th>{/heading_next_cell}
		{heading_row_end}</tr>{/heading_row_end}
		{week_row_start}<tr>{/week_row_start}
		{week_day_cell}<td class="cl_wday">{week_day}</td>{/week_day_cell}
		{week_row_end}</tr>{/week_row_end}
		{cal_row_start}<tr class="days">{/cal_row_start}
		{cal_cell_start}<td class="day">{/cal_cell_start}
		{cal_cell_content}
		<div class="day_num">{day}</div>
		<div class="content">{content}</div>
		{/cal_cell_content}
		{cal_cell_content_today}
		<div class="day_num highlight">{day}</div>
		<div class="content">{content}</div>
		{/cal_cell_content_today}
		{cal_cell_no_content}<div class="day_num">{day}</div>{/cal_cell_no_content}
		{cal_cell_no_content_today}<div class="day_num highlight">{day}</div>{/cal_cell_no_content_today}
		{cal_cell_blank}&nbsp;{/cal_cell_blank}
		{cal_cell_end}</td>{/cal_cell_end}
		{cal_row_end}</tr>{/cal_row_end}
		{table_close}</table></div>{/table_close}';

        $this->load->library('calendar', $config);
        $sales = $user_id ? $this->reports_model->getStaffDailySales($user_id, $year, $month, $warehouse_id) : $this->reports_model->getDailySales($year, $month, $warehouse_id);
        $sales_w = $user_id ? $this->reports_model->getStaffDailySales_w($user_id, $year, $month, $warehouse_id) : $this->reports_model->getDailySales_w($year, $month, $warehouse_id);



        if (!empty($sales)) {
            foreach ($sales as $sale) {

                //$daily_sale[$sale->date] = "<table class='table table-bordered table-hover table-striped table-condensed data' style='margin:0;'><tr><td>" . lang("discount") . "</td><td>" . $this->sma->formatMoney($sale->discount) . "</td></tr><tr><td>" . lang("shipping") . "</td><td>" . $this->sma->formatMoney($sale->shipping) . "</td></tr><tr style='cursor: pointer' onClick='getsaleitemstaxes(" . $year . "," . $month . "," . $sale->date . ")'><td>" . lang("product_tax") . " <i class='fa fa-list-alt' aria-hidden='true'></i></td><td>" . $this->sma->formatMoney($sale->tax1) . "</td></tr><tr><td>" . lang("order_tax") . "</td><td>" . $this->sma->formatMoney($sale->tax2) . "</td></tr><tr><td>" . lang("total") . "</td><td>" . $this->sma->formatMoney($sale->total) . "</td></tr><tr><td>Items</td><td onClick='getsaleitems(" . $year . "," . $month . "," . $sale->date . ")'><i class='fa fa-list-alt' aria-hidden='true'></i></td></tr></table>";
                
                $daily_sale[$sale->date] = "<table class='table table-bordered table-hover table-striped table-condensed data' style='margin:0;'><tr><td>" . lang("discount") . "</td><td>" . $this->sma->formatMoney($sale->discount) . "</td></tr><tr><td>" . lang("shipping") . "</td><td>" . $this->sma->formatMoney($sale->shipping) . "</td></tr><tr style='cursor: pointer' onClick='getsaleitemstaxes(" . $year . "," . $month . "," . $sale->date . ")'><td>" . lang("product_tax") . " <i class='fa fa-list-alt' aria-hidden='true'></i></td><td>" . $this->sma->formatMoney($sale->tax1) . "</td></tr><tr><td>" . lang("order_tax") . "</td><td>" . $this->sma->formatMoney($sale->tax2) . "</td></tr><tr><td>" . lang("total") . "</td><td>" . $this->sma->formatMoney($sale->total) . "</td></tr><tr><td>Items</td><td onClick='getsaleitems(" . $year . "," . $month . "," . $sale->date . ")'><i class='fa fa-list-alt' aria-hidden='true'></i></td></tr><tr style='cursor: pointer' onClick='getsaleitemurbin(" . $year . "," . $month . "," . $sale->date . ")'><td>" . lang("Urban_Piper") . " <i class='fa fa-list-alt' aria-hidden='true'></i></td><td>" . $this->sma->formatMoney($sale->urban_piper) . "</td></tr></table>";
          
            }
        } else {
            $daily_sale = array();
        }

        $this->data['calender'] = $this->calendar->generate($year, $month, $daily_sale);
        $this->data['year'] = $year;
        $this->data['month'] = $month;

        if ($pdf) {
            $sales_pdf = array();
            //foreach($sales as $data_row)
            foreach ($sales_w as $data_row) {//because of warehouse show
                $sales_pdf[$data_row->date] = $data_row;
            }
            sort($sales_pdf);

            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'DDDDDD'))));

            $this->excel->getActiveSheet()->getStyle("A1:H1")->applyFromArray($style);

            $this->excel->getActiveSheet()->mergeCells('A1:H1');
            $this->excel->getActiveSheet()->SetCellValue('A1', lang('Daily Sales Report ') . date("M-Y", mktime(0, 0, 0, $month, 1, $year)));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('Sr.No'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Date'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Discount'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Shipping'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Product Tax'));
            $this->excel->getActiveSheet()->SetCellValue('F2', lang('Order Tax'));
            $this->excel->getActiveSheet()->SetCellValue('G2', lang('Total'));
            $this->excel->getActiveSheet()->SetCellValue('H2', lang('Urbin Piper'));
            $this->excel->getActiveSheet()->SetCellValue('I2', lang('Warehouse'));
            $row = 3;

            $sr = 1;
            foreach ($sales_pdf as $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sr);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->date . '/' . $month . '/' . $year);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $this->sma->formatMoney($data_row->discount));
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $this->sma->formatMoney($data_row->shipping));
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $this->sma->formatMoney($data_row->tax1));
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $this->sma->formatMoney($data_row->tax2));
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $this->sma->formatMoney($data_row->total));
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $this->sma->formatMoney($data_row->urban_piper));
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->warehouse);

                $row++;
                $sr++;
            }
            $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
            $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);

            $this->excel->getActiveSheet()->getStyle("A2:I" . ($row - 1))->applyFromArray($style);

            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $filename = 'daily_sales_report';

            if ($pdf == 'pdf') {
                $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                $rendererLibrary = 'MPDF';
                $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                    die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                }

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                header('Cache-Control: max-age=0');

                $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                $objWriter->save('php://output');
                exit();
            } elseif ($pdf == 'xls') {
                $this->excel->getActiveSheet()->getStyle('E2:E' . $row)->getAlignment()->setWrapText(TRUE);
                ob_clean();
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                header('Cache-Control: max-age=0');
                ob_clean();
                $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                $objWriter->save('php://output');
                exit();
            } elseif ($pdf == 'img') {
                $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                $rendererLibrary = 'MPDF';
                $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                    die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                }

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                header('Cache-Control: max-age=0');

                $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/daily_sales_report.pdf', __FILE__));
                redirect("reports/create_image/daily_sales_report.pdf");
            }
        }


        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse_id'] = $this->session->userdata('warehouse_id');
        //$this->data['sel_warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        $this->data['active_warehouse_id'] = $warehouse_id == '' ? 0 : $warehouse_id;
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('daily_sales_report')));
        $meta = array('page_title' => lang('daily_sales_report'), 'bc' => $bc);
        $this->page_construct('reports/daily', $meta, $this->data);
    }

    public function daily_sales_items() {
        $date = $_GET['date'];
        $warehouse_id = $_GET['active_warehouse_id'];
        if (empty($date))
            return FALSE;

        $sale_data = $this->reports_model->getDailySalesItems($date, $warehouse_id);
        ?>
        <button type="button" type="button" class="btn btn-sm btn-default no-print pull-right" style="margin-right:10px;margin-bottom:10px;" onClick="printdiv('<?php echo $date; ?>');"><i class="fa fa-print"></i><?= lang('print'); ?>
        </button>
        <div class="table-responsive" id="dailysalesitemtable">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Code</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Units</th>
                        <th>Tax Rate</th>
                        <th>Tax Amount</th>
                        <th>Discount</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 0;
                    foreach ($sale_data as $key => $item) {
                        ?>
                        <tr>
                            <td><?= ++$i ?></td>
                            <td><?= $item->product_name ?></td>
                            <td><?= $item->product_code ?></td>
                            <td><?= $item->category_name ?></td>
                            <td><?= $this->sma->formatMoney($item->net_unit_price) ?></td>
                            <td><?= number_format($item->qty, 2) ?></td>
                            <td><?= $item->unit ?></td>
                            <td><?= $item->tax_rate ? number_format($item->tax_rate, 2) : 0; ?>%</td>
                            <td><?= $this->sma->formatMoney($item->tax) ?></td>
                            <td><?= $this->sma->formatMoney($item->discount) ?></td>
                            <td><?= $this->sma->formatMoney($item->total) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php
    }

    public function daily_sales_items_taxes() {
        $date = $_GET['date'];
        $warehouse_id = $_GET['active_warehouse_id'];
        if (empty($date))
            return FALSE;

        $saletax_data = $this->reports_model->getDailySalesItemsTaxes($date, $warehouse_id);
        ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tax</th>
                        <th>CGST</th>
                        <th>SGST</th>
                        <th>IGST</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalTax = 0;
                    if (!empty($saletax_data)) {
                        foreach ($saletax_data as $key => $item) {
                            $totalTax += $item->amount;
                            $gst_tax = $this->reports_model->gettaxitemid($item->item_id);
                            ?>
                            <tr>
                                <td class="text-center">GST <?= $item->rate ?>%</td>
                                <?php foreach ($gst_tax as $key => $tax) { ?>
                                    <td class="text-center"><?= ($tax->CGST ? $tax->CGST : 0) ?>%</td>
                                    <td class="text-center"><?= ($tax->SGST ? $tax->SGST : 0) ?>%</td>
                                    <td class="text-center"><?= ($tax->IGST ? $tax->IGST : 0) ?>%</td>
                                <?php } ?>
                                <td class="text-center">Rs. <?= number_format($item->amount, 2); ?></td>
                            </tr>
                        <?php }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total Tax</th>
                        <th class="text-center">Rs. <?= number_format($totalTax, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php
    }

    function monthly_sales($warehouse_id = NULL, $year = NULL, $pdf = NULL, $user_id = NULL) {

        $this->sma->checkPermissions();
        if ($warehouse_id != NULL) {
            $warehouse_id = $warehouse_id;
        } else if (!$this->Owner && !$this->Admin && $this->session->userdata('warehouse_id')) {
            $warehouse_id = str_replace(",", "_", $this->session->userdata('warehouse_id'));
        }
        if (!$year) {
            $year = date('Y');
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->load->language('calendar');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['year'] = $year;
        $this->data['sales'] = $user_id ? $this->reports_model->getStaffMonthlySales($user_id, $year, $warehouse_id) : $this->reports_model->getMonthlySales($year, $warehouse_id);
        $_sales = $this->data['sales'];

        $_sales_w = $user_id ? $this->reports_model->getStaffMonthlySales_w($user_id, $year, $warehouse_id) : $this->reports_model->getMonthlySales_w($year, $warehouse_id);

        if ($pdf) {
            $sales_pdf = array();
            //foreach($_sales as $data_row)
            foreach ($_sales_w as $data_row) {
                $sales_pdf[$data_row->date] = $data_row;
            }
            sort($sales_pdf);

            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'DDDDDD'))));

            $this->excel->getActiveSheet()->getStyle("A1:H1")->applyFromArray($style);

            $this->excel->getActiveSheet()->mergeCells('A1:H1');
            $this->excel->getActiveSheet()->SetCellValue('A1', lang('Monthly Sales Report ') . date("Y", mktime(0, 0, 0, 1, 1, $year)));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('Sr.No'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Date'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Discount'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Shipping'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Product Tax'));
            $this->excel->getActiveSheet()->SetCellValue('F2', lang('Order Tax'));
            $this->excel->getActiveSheet()->SetCellValue('G2', lang('Total'));
            $this->excel->getActiveSheet()->SetCellValue('H2', lang('Warehouse'));

            $row = 3;

            $sr = 1;
            foreach ($sales_pdf as $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sr);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, date("M-Y", mktime(0, 0, 0, $data_row->date, 1, $year)));
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $this->sma->formatMoney($data_row->discount));
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $this->sma->formatMoney($data_row->shipping));
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $this->sma->formatMoney($data_row->tax1));
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $this->sma->formatMoney($data_row->tax2));
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $this->sma->formatMoney($data_row->total));
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->warehouse);
                $row++;
                $sr++;
            }
            $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
            $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);


            $this->excel->getActiveSheet()->getStyle("A2:G" . ($row - 1))->applyFromArray($style);

            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $filename = 'monthly_sales_report';

            if ($pdf == 'pdf') {
                $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                $rendererLibrary = 'MPDF';
                $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                    die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                }

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                header('Cache-Control: max-age=0');

                $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                $objWriter->save('php://output');
                exit();
            } elseif ($pdf == 'xls') {
                $this->excel->getActiveSheet()->getStyle('E2:E' . $row)->getAlignment()->setWrapText(TRUE);
                ob_clean();
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                header('Cache-Control: max-age=0');
                ob_clean();
                $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                $objWriter->save('php://output');
                exit();
            } elseif ($pdf == 'img') {
                $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                $rendererLibrary = 'MPDF';
                $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                    die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                }

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                header('Cache-Control: max-age=0');

                $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/monthly_sales_report.pdf', __FILE__));
                redirect("reports/create_image/monthly_sales_report.pdf");
                exit();
            }
        }


        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse_id'] = $this->session->userdata('warehouse_id');
//        $expwarehouse = explode(",", $warehouse_id); 
//        foreach($expwarehouse as $expw){
//            $getwerehouse[]= $warehouse_id ? $this->site->getWarehouseByID($expw) : NULL;
//        }
//        echo $warehouse_id;exit;
        $this->data['sel_warehouse'] = $warehouse_id ? (strpos($warehouse_id, '_') !== false) ? NULL : $this->site->getWarehouseByID($warehouse_id) : NULL; // $getwerehouse;
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('monthly_sales_report')));
        $meta = array('page_title' => lang('monthly_sales_report'), 'bc' => $bc);
        $this->page_construct('reports/monthly', $meta, $this->data);
    }

    public function monthly_sales_items_taxes() {
        $month = $_GET['month'];
        $year = $_GET['year'];

        if (empty($month) || empty($year))
            return FALSE;

        $saletax_data = $this->reports_model->getMonthSalesItemsTaxes($month, $year);
        ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tax</th>
                        <th>CGST</th>
                        <th>SGST</th>
                        <th>IGST</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalTax = 0;
                    foreach ($saletax_data as $key => $item) {
                        $totalTax += $item->amount;
                        $gst_tax = $this->reports_model->gettaxitemid($item->item_id);
                        ?>
                        <tr>
                            <td class="text-center">GST <?= $item->rate ?>%</td>
            <?php foreach ($gst_tax as $key => $tax) { ?>
                                <td class="text-center"><?= ($tax->CGST ? $tax->CGST : 0) ?>%</td>
                                <td class="text-center"><?= ($tax->SGST ? $tax->SGST : 0) ?>%</td>
                                <td class="text-center"><?= ($tax->IGST ? $tax->IGST : 0) ?>%</td>
            <?php } ?>
                            <td class="text-center">Rs. <?= number_format($item->amount, 2); ?></td>
                        </tr>
        <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total Tax</th>
                        <th class="text-center">Rs. <?= number_format($totalTax, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php
    }

    function sales() {
        $this->sma->checkPermissions('sales');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['salecount'] = $this->getCountSales();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->page_construct('reports/sales', $meta, $this->data);
    }

    function getSalesReport($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('sales', TRUE);
        $start = '';
        $limit = '';
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $biller = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $serial = $this->input->get('serial') ? $this->input->get('serial') : NULL;

        $strtlimit = $this->input->get('strtlimit') ? $this->input->get('strtlimit') : NULL;
        $limt = explode("-", $strtlimit);
        if ($limt) {
            $start = $limt[0];
            $limit = $limt[1];
        }
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        if ($pdf || $xls || $img) {

            /* $this->db->select("date,reference_no, biller, customer, GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('sale_items') . ".product_name, ' (', " . $this->db->dbprefix('sale_items') . ".quantity, ')') SEPARATOR '\n') as iname, grand_total, paid, payment_status", FALSE)->from('sales')->join('sale_items', 'sale_items.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left')->group_by('sales.id')->order_by('sales.date desc'); */

            $this->db->select("sales.id as sale_id,sales.date as date,sales.reference_no,sales.invoice_no, sales.biller, sales.customer, sales.grand_total, sales.paid, sales.rounding, sales.payment_status", FALSE)->from('sales')->join('sale_items', 'sale_items.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');

            /* $this->db->select("sales.id as sale_id,sales.date as date,sales.reference_no, sales.biller, sales.customer, sales.grand_total, sales.paid, sales.rounding,sales.payment_status", FALSE)->from('sales')->group_by('sales.id')->order_by('sales.date desc'); */

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->datatables->where('sales.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('sales.created_by', $user);
                    }
                }
            }
            if ($product) {
                $this->db->where('sale_items.product_id', $product);
            }
            $this->db->group_by('sales.id');
            $this->db->order_by('sales.date desc');
            if ($serial) {
                $this->db->like('sale_items.serial_no', $serial);
            }
            if ($biller) {
                $this->db->where('sales.biller_id', $biller);
            }
            if ($customer) {
                $this->db->where('sales.customer_id', $customer);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('sales.warehouse_id IN(' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->db->like('sales.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->db->where('DATE('.$this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            if ($limit != '' && $start != '') {
                $this->db->limit($limit, $start);
            }
            $q = $this->db->get();
            $data_sales = [];
            $products = '';
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    if (!in_array($row->sale_id, $data_sales)) {
                        $data_sales[] = $row->sale_id;
                    }
                    // $data[$row->sale_id]['sale_id'] = $row->sale_id;
                    $id = $row->sale_id;
                    $data[$id]['date'] = $row->date;

                    $data[$id]['reference_no'] = $row->reference_no;
                    $data[$id]['invoice_no'] = $row->invoice_no;
                    $data[$id]['biller'] = $row->biller;
                    $data[$id]['customer'] = $row->customer;
                    $data[$id]['grand_total'] = $row->grand_total + $row->rounding;
                    $data[$id]['paid'] = $row->paid;
                    $data[$id]['balance'] = $row->grand_total + $row->rounding - $row->paid;
                    $data[$id]['payment_status'] = $row->payment_status;
                    //$data[] = $row;
                    //   print_r($data);exit;
                }//forloop

                if ($product) {
                    $products = $product;
                }
                $uniqueSalesIds = array_unique($data_sales);

                $SalesItems = $this->reports_model->getSalesItemsBySaleIds($uniqueSalesIds, $products);

                if (is_array($SalesItems)) {
                    foreach ($SalesItems as $key => $SaleItemsRow) {
                        //Sales Items Details
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['name'] = $SaleItemsRow->product_name;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['quantity'] = $SaleItemsRow->quantity;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['variantname'] = ($SaleItemsRow->variant_name) ? $SaleItemsRow->variant_name : '';
                    }//end foreach
                }//end if
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:L1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:L1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Sales Report');
                $this->excel->getActiveSheet()->setTitle(lang('sales_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('invoice_no'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('biller'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('customer'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('product'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Varient'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('balance'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('payment_status'));

                $row = 3;
                $total = 0;
                $paid = 0;
                $balance = 0;
                foreach ($data as $sale_id => $salesdata) {
                    $data_row = (object) $salesdata;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->invoice_no);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->biller);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->customer);
                    // $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->iname);
                    $rowitems = $row;
                    if (!empty($data_row->items)) {
                        foreach ($data_row->items as $saleitem_id => $salesItemsData) {
                            $sales_items_data = (object) $salesItemsData;
                            $this->excel->getActiveSheet()->SetCellValue('F' . $row, $sales_items_data->name);
                            $this->excel->getActiveSheet()->SetCellValue('G' . $row, $sales_items_data->variantname);
                            $this->excel->getActiveSheet()->SetCellValue('H' . $row, $sales_items_data->quantity);
                            $row++;
                        }//end foreach
                        $this->excel->getActiveSheet()->SetCellValue('I' . $rowitems, $data_row->grand_total);
                        $this->excel->getActiveSheet()->SetCellValue('J' . $rowitems, $data_row->paid);
                        $this->excel->getActiveSheet()->SetCellValue('K' . $rowitems, ($data_row->grand_total - $data_row->paid));
                        $this->excel->getActiveSheet()->SetCellValue('L' . $rowitems, lang($data_row->payment_status));
                    }//end if.

                    $total += $data_row->grand_total + $data_row->rounding;
                    $paid += $data_row->paid;
                    $balance += ($data_row->grand_total + $data_row->rounding - $data_row->paid);
                    //$row++;
                }
                $this->excel->getActiveSheet()->getStyle("I" . $row . ":K" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $total);
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $paid);
                $this->excel->getActiveSheet()->SetCellValue('K' . $row, $balance);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
                $filename = 'sales_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {

                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    $this->excel->getActiveSheet()->getStyle('E2:E' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {

                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/sales_report.pdf', __FILE__));
                    redirect("reports/create_image/sales_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            /* $si = "( SELECT sale_id, product_id, serial_no, GROUP_CONCAT(CONCAT({$this->db->dbprefix('sale_items')}.product_name, '__', {$this->db->dbprefix('sale_items')}.quantity) SEPARATOR '___') as item_nane from {$this->db->dbprefix('sale_items')} ";
              if($product)
              {
              $si .= " WHERE {$this->db->dbprefix('sale_items')}.product_id = {$product} ";
              }
              $si .= " GROUP BY {$this->db->dbprefix('sale_items')}.sale_id ) FSI"; */
            $si = "( SELECT {$this->db->dbprefix('sale_items')}.sale_id, {$this->db->dbprefix('sale_items')}.product_id, {$this->db->dbprefix('sale_items')}.serial_no, GROUP_CONCAT(CONCAT('',{$this->db->dbprefix('sale_items')}.product_name, IF({$this->db->dbprefix('product_variants')}.name <> 'NULL',CONCAT(' (',{$this->db->dbprefix('product_variants')}.name,')'),''), '__', {$this->db->dbprefix('sale_items')}.quantity) SEPARATOR '___') as item_nane from {$this->db->dbprefix('sale_items')} ";
            $si .= "LEFT JOIN {$this->db->dbprefix('product_variants')} ON {$this->db->dbprefix('sale_items')}.option_id = {$this->db->dbprefix('product_variants')}.id";
            if ($product) {
                $si .= " WHERE {$this->db->dbprefix('sale_items')}.product_id = {$product} ";
            }

            $si .= " GROUP BY {$this->db->dbprefix('sale_items')}.sale_id ) FSI";
            $this->load->library('datatables');
//REPLACE(reference_no, SUBSTRING_INDEX(reference_no, '/', -1), {$this->db->dbprefix('sales')}.id) as reference_no
            $this->datatables->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference_no, invoice_no,biller, customer, FSI.item_nane as iname, grand_total+rounding, paid, (grand_total+rounding -paid) as balance, payment_status, {$this->db->dbprefix('sales')}.id as id", FALSE)->from('sales')->join($si, 'FSI.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');
            // ->group_by('sales.id');

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->datatables->where('sales.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('sales.created_by', $user);
                    }
                }
            }
            if ($product) {
                $this->datatables->where('FSI.product_id', $product, FALSE);
            }
            if ($serial) {
                $this->datatables->like('FSI.serial_no', $serial, FALSE);
            }
            if ($biller) {
                $this->datatables->where('sales.biller_id', $biller);
            }
            if ($customer) {
                $this->datatables->where('sales.customer_id', $customer);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('sales.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->datatables->like('sales.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->datatables->where('DATE('.$this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    /* Count sales report */

    public function getCountSales() {

        $this->sma->checkPermissions('sales', TRUE);
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $biller = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $serial = $this->input->get('serial') ? $this->input->get('serial') : NULL;


        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }
        $this->db->select("date,reference_no, biller, customer, GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('sale_items') . ".product_name, ' (', " . $this->db->dbprefix('sale_items') . ".quantity, ')') SEPARATOR '\n') as iname, grand_total, paid, payment_status", FALSE)->from('sales')->join('sale_items', 'sale_items.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left')->group_by('sales.id')->order_by('sales.date desc');

        if ($this->Owner || $this->Admin) {
            if ($this->input->get('user')) {
                $this->datatables->where('sales.created_by', $this->input->get('user'));
            }
        } else {
            if ($this->session->userdata('view_right') == '0') {
                if ($user) {
                    $this->datatables->where('sales.created_by', $user);
                }
            }
        }
        if ($product) {
            $this->db->where('sale_items.product_id', $product);
        }
        if ($serial) {
            $this->db->like('sale_items.serial_no', $serial);
        }
        if ($biller) {
            $this->db->where('sales.biller_id', $biller);
        }
        if ($customer) {
            $this->db->where('sales.customer_id', $customer);
        }
        if ($warehouse) {
            $getwarehouse = str_replace("_", ",", $warehouse);
            $this->db->where('sales.warehouse_id IN(' . $getwarehouse . ')');
        }
        if ($reference_no) {
            $this->db->like('sales.reference_no', $reference_no, 'both');
        }
        if ($start_date) {
            $this->db->where($this->db->dbprefix('sales') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
        }

        $q = $this->db->get();
        if ($q->num_rows() > 0) {
            // foreach(($q->result()) as $row)
            //{
            //$data[] = $row;
            $data = $q->num_rows();
            //}
            return $data;
        }

        return FALSE;
    }

    function getQuotesReport($pdf = NULL, $xls = NULL) {

        if ($this->input->get('product')) {
            $product = $this->input->get('product');
        } else {
            $product = NULL;
        }
        if ($this->input->get('user')) {
            $user = $this->input->get('user');
        } else {
            $user = NULL;
        }
        if ($this->input->get('customer')) {
            $customer = $this->input->get('customer');
        } else {
            $customer = NULL;
        }
        if ($this->input->get('biller')) {
            $biller = $this->input->get('biller');
        } else {
            $biller = NULL;
        }
        if ($this->input->get('warehouse')) {
            $warehouse = $this->input->get('warehouse');
        } else {
            $warehouse = NULL;
        }
        if ($this->input->get('reference_no')) {
            $reference_no = $this->input->get('reference_no');
        } else {
            $reference_no = NULL;
        }
        if ($this->input->get('start_date')) {
            $start_date = $this->input->get('start_date');
        } else {
            $start_date = NULL;
        }
        if ($this->input->get('end_date')) {
            $end_date = $this->input->get('end_date');
        } else {
            $end_date = NULL;
        }
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if ($pdf || $xls) {

            $this->db->select("date, reference_no, biller, customer, GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('quote_items') . ".product_name, ' (', " . $this->db->dbprefix('quote_items') . ".quantity, ')') SEPARATOR '<br>') as iname, grand_total, status", FALSE)->from('quotes')->join('quote_items', 'quote_items.quote_id=quotes.id', 'left')->join('warehouses', 'warehouses.id=quotes.warehouse_id', 'left')->group_by('quotes.id');

            if ($user) {
                $this->db->where('quotes.created_by', $user);
            }
            if ($product) {
                $this->db->where('quote_items.product_id', $product);
            }
            if ($biller) {
                $this->db->where('quotes.biller_id', $biller);
            }
            if ($customer) {
                $this->db->where('quotes.customer_id', $customer);
            }
            if ($warehouse) {
                $this->db->where('quotes.warehouse_id', $warehouse);
            }
            if ($reference_no) {
                $this->db->like('quotes.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->db->where($this->db->dbprefix('quotes') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $this->excel->getActiveSheet()->setTitle(lang('quotes_report'));
                $this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B1', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C1', lang('biller'));
                $this->excel->getActiveSheet()->SetCellValue('D1', lang('customer'));
                $this->excel->getActiveSheet()->SetCellValue('E1', lang('product_qty'));
                $this->excel->getActiveSheet()->SetCellValue('F1', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('G1', lang('status'));

                $row = 2;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->biller);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->customer);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->iname);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->status);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $filename = 'quotes_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    $this->excel->getActiveSheet()->getStyle('E2:E' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $qi = "( SELECT quote_id, product_id, GROUP_CONCAT(CONCAT({$this->db->dbprefix('quote_items')}.product_name, '__', {$this->db->dbprefix('quote_items')}.quantity) SEPARATOR '___') as item_nane from {$this->db->dbprefix('quote_items')} ";
            if ($product) {
                $pi .= " WHERE {$this->db->dbprefix('quote_items')}.product_id = {$product} ";
            }
            $qi .= " GROUP BY {$this->db->dbprefix('quote_items')}.quote_id ) FQI";
            $this->load->library('datatables');
            $this->datatables->select("date, reference_no, biller, customer, FQI.item_nane as iname, grand_total, status, {$this->db->dbprefix('quotes')}.id as id", FALSE)->from('quotes')->join($qi, 'FQI.quote_id=quotes.id', 'left')->join('warehouses', 'warehouses.id=quotes.warehouse_id', 'left')->group_by('quotes.id');

            if ($user) {
                $this->datatables->where('quotes.created_by', $user);
            }
            if ($product) {
                $this->datatables->where('FQI.product_id', $product, FALSE);
            }
            if ($biller) {
                $this->datatables->where('quotes.biller_id', $biller);
            }
            if ($customer) {
                $this->datatables->where('quotes.customer_id', $customer);
            }
            if ($warehouse) {
                $this->datatables->where('quotes.warehouse_id', $warehouse);
            }
            if ($reference_no) {
                $this->datatables->like('quotes.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->datatables->where($this->db->dbprefix('quotes') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function getTransfersReport($pdf = NULL, $xls = NULL) {
        if ($this->input->get('product')) {
            $product = $this->input->get('product');
        } else {
            $product = NULL;
        }

        if ($pdf || $xls) {

            $this->db->select($this->db->dbprefix('transfers') . ".date, transfer_no, (CASE WHEN " . $this->db->dbprefix('transfers') . ".status = 'completed' THEN  GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('purchase_items') . ".product_name, ' (', " . $this->db->dbprefix('purchase_items') . ".quantity, ')') SEPARATOR '<br>') ELSE GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('transfer_items') . ".product_name, ' (', " . $this->db->dbprefix('transfer_items') . ".quantity, ')') SEPARATOR '<br>') END) as iname, from_warehouse_name as fname, from_warehouse_code as fcode, to_warehouse_name as tname,to_warehouse_code as tcode, grand_total, " . $this->db->dbprefix('transfers') . ".status")->from('transfers')->join('transfer_items', 'transfer_items.transfer_id=transfers.id', 'left')->join('purchase_items', 'purchase_items.transfer_id=transfers.id', 'left')->group_by('transfers.id')->order_by('transfers.date desc');
            if ($product) {
                $this->db->where($this->db->dbprefix('purchase_items') . ".product_id", $product);
                $this->db->or_where($this->db->dbprefix('transfer_items') . ".product_id", $product);
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $this->excel->getActiveSheet()->setTitle(lang('transfers_report'));
                $this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B1', lang('transfer_no'));
                $this->excel->getActiveSheet()->SetCellValue('C1', lang('product_qty'));
                $this->excel->getActiveSheet()->SetCellValue('D1', lang('warehouse') . ' (' . lang('from') . ')');
                $this->excel->getActiveSheet()->SetCellValue('E1', lang('warehouse') . ' (' . lang('to') . ')');
                $this->excel->getActiveSheet()->SetCellValue('F1', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('G1', lang('status'));

                $row = 2;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->transfer_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->iname);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->fname . ' (' . $data_row->fcode . ')');
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->tname . ' (' . $data_row->tcode . ')');
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->status);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $filename = 'transfers_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    $this->excel->getActiveSheet()->getStyle('C2:C' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            $this->datatables->select("{$this->db->dbprefix('transfers')}.date, transfer_no, (CASE WHEN {$this->db->dbprefix('transfers')}.status = 'completed' THEN  GROUP_CONCAT(CONCAT({$this->db->dbprefix('purchase_items')}.product_name, '__', {$this->db->dbprefix('purchase_items')}.quantity) SEPARATOR '___') ELSE GROUP_CONCAT(CONCAT({$this->db->dbprefix('transfer_items')}.product_name, '__', {$this->db->dbprefix('transfer_items')}.quantity) SEPARATOR '___') END) as iname, from_warehouse_name as fname, from_warehouse_code as fcode, to_warehouse_name as tname,to_warehouse_code as tcode, grand_total, {$this->db->dbprefix('transfers')}.status, {$this->db->dbprefix('transfers')}.id as id", FALSE)->from('transfers')->join('transfer_items', 'transfer_items.transfer_id=transfers.id', 'left')->join('purchase_items', 'purchase_items.transfer_id=transfers.id', 'left')->group_by('transfers.id');
            if ($product) {
                $this->datatables->where(" (({$this->db->dbprefix('purchase_items')}.product_id = {$product}) OR ({$this->db->dbprefix('transfer_items')}.product_id = {$product})) ", NULL, FALSE);
            }
            $this->datatables->edit_column("fname", "$1 ($2)", "fname, fcode")->edit_column("tname", "$1 ($2)", "tname, tcode")->unset_column('fcode')->unset_column('tcode');
            echo $this->datatables->generate();
        }
    }

    function purchases() {
        $this->sma->checkPermissions('purchases');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('purchases_report')));
        $meta = array('page_title' => lang('purchases_report'), 'bc' => $bc);
        $this->page_construct('reports/purchases', $meta, $this->data);
    }

    function view_image_format() {
        $this->sma->checkPermissions('purchases', TRUE);
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $supplier = $this->input->get('supplier') ? $this->input->get('supplier') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }



        $this->db->select("" . $this->db->dbprefix('purchases') . ".date, reference_no, " . $this->db->dbprefix('warehouses') . ".name as wname, supplier, GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('purchase_items') . ".product_name, ' (', " . $this->db->dbprefix('purchase_items') . ".quantity, ')') SEPARATOR '\n') as iname, grand_total, paid, " . $this->db->dbprefix('purchases') . ".status", FALSE)->from('purchases')->join('purchase_items', 'purchase_items.purchase_id=purchases.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left')->group_by('purchases.id')->order_by('purchases.date desc');

        if ($this->session->userdata('view_right') == '0') {
            if ($user) {
                $this->db->where('purchases.created_by', $user);
            }
        }
        if ($product) {
            $this->db->where('purchase_items.product_id', $product);
        }
        if ($supplier) {
            $this->db->where('purchases.supplier_id', $supplier);
        }
        if ($warehouse) {
            $getwarehouse = str_replace("_", ",", $warehouse);
            $this->db->where('purchases.warehouse_id IN (' . $getwarehouse . ')');
        }
        if ($reference_no) {
            $this->db->like('purchases.reference_no', $reference_no, 'both');
        }
        if ($start_date) {
            $this->db->where($this->db->dbprefix('purchases') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
        }

        $q = $this->db->get();
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
        } else {
            $data = NULL;
        }

        if (!empty($data)) {

            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);

            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

            $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
            $this->excel->getActiveSheet()->mergeCells('A1:I1');
            $this->excel->getActiveSheet()->SetCellValue('A1', 'Purchases Report');

            $this->excel->getActiveSheet()->setTitle(lang('purchase_report'));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('warehouse'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('supplier'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('product_qty'));
            $this->excel->getActiveSheet()->SetCellValue('F2', lang('grand_total'));
            $this->excel->getActiveSheet()->SetCellValue('G2', lang('paid'));
            $this->excel->getActiveSheet()->SetCellValue('H2', lang('balance'));
            $this->excel->getActiveSheet()->SetCellValue('I2', lang('status'));

            $row = 3;
            $total = 0;
            $paid = 0;
            $balance = 0;
            foreach ($data as $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->wname);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->supplier);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->iname);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->grand_total);
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->paid);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, ($data_row->grand_total - $data_row->paid));
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->status);
                $total += $data_row->grand_total;
                $paid += $data_row->paid;
                $balance += ($data_row->grand_total - $data_row->paid);
                $row++;
            }
            $this->excel->getActiveSheet()->getStyle("F" . $row . ":H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
            $this->excel->getActiveSheet()->SetCellValue('F' . $row, $total);
            $this->excel->getActiveSheet()->SetCellValue('G' . $row, $paid);
            $this->excel->getActiveSheet()->SetCellValue('H' . $row, $balance);

            $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $filename = 'purchase_report';
            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


            $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
            $this->excel->getDefaultStyle()->applyFromArray($styleArray);
            $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
            require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
            $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
            $rendererLibrary = 'MPDF';
            $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
            if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
            header('Cache-Control: max-age=0');

            $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
            //$objWriter->save('php://output');
            $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/purchase.pdf', __FILE__));
            redirect("reports/create_image/purchase.pdf");
        }
    }

    function getPurchasesReport($pdf = NULL, $xls = NULL) {
        $this->sma->checkPermissions('purchases', TRUE);

        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $supplier = $this->input->get('supplier') ? $this->input->get('supplier') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        if ($pdf || $xls) {

            $this->db->select("" . $this->db->dbprefix('purchases') . ".date, reference_no, " . $this->db->dbprefix('warehouses') . ".name as wname, supplier, GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('purchase_items') . ".product_name, ' (', " . $this->db->dbprefix('purchase_items') . ".quantity, ')') SEPARATOR '\n') as iname, (grand_total+rounding) as grand_total, paid, " . $this->db->dbprefix('purchases') . ".status", FALSE)->from('purchases')->join('purchase_items', 'purchase_items.purchase_id=purchases.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left')->group_by('purchases.id')->order_by('purchases.date desc');

            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->db->where('purchases.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('purchases.created_by', $user);
                    }
                }
            }
            if ($product) {
                $this->db->where('purchase_items.product_id', $product);
            }
            if ($supplier) {
                $this->db->where('purchases.supplier_id', $supplier);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('purchases.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->db->like('purchases.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->db->where('DATE('.$this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:I1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Purchases Report');

                $this->excel->getActiveSheet()->setTitle(lang('purchase_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('warehouse'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('supplier'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('product_qty'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('balance'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('status'));

                $row = 3;
                $total = 0;
                $paid = 0;
                $balance = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->wname);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->supplier);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->iname);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->paid);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, ($data_row->grand_total - $data_row->paid));
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->status);
                    $total += $data_row->grand_total;
                    $paid += $data_row->paid;
                    $balance += ($data_row->grand_total - $data_row->paid);
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("F" . $row . ":H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $total);
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $paid);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $balance);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
                $filename = 'purchase_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    $this->excel->getActiveSheet()->getStyle('E2:E' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $pi = "( SELECT purchase_id, product_id, (GROUP_CONCAT(CONCAT({$this->db->dbprefix('purchase_items')}.product_name, '__', {$this->db->dbprefix('purchase_items')}.quantity) SEPARATOR '___')) as item_nane from {$this->db->dbprefix('purchase_items')} ";
            if ($product) {
                $pi .= " WHERE {$this->db->dbprefix('purchase_items')}.product_id = {$product} ";
            }
            $pi .= " GROUP BY {$this->db->dbprefix('purchase_items')}.purchase_id ) FPI";

            $this->load->library('datatables');
            $this->datatables->select("DATE_FORMAT({$this->db->dbprefix('purchases')}.date, '%Y-%m-%d %T') as date, reference_no, {$this->db->dbprefix('warehouses')}.name as wname, supplier, (FPI.item_nane) as iname, (grand_total+rounding) as grand_total, paid, (grand_total+rounding-paid) as balance, {$this->db->dbprefix('purchases')}.status, {$this->db->dbprefix('purchases')}.id as id", FALSE)->from('purchases')->join($pi, 'FPI.purchase_id=purchases.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left');
            // ->group_by('purchases.id');

            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->db->where('purchases.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('purchases.created_by', $user);
                    }
                }
            }
            if ($product) {
                $this->datatables->where('FPI.product_id', $product, FALSE);
            }
            if ($supplier) {
                $this->datatables->where('purchases.supplier_id', $supplier);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('purchases.warehouse_id IN(' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->datatables->like('purchases.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->datatables->where('DATE('.$this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function payments() {
        $this->sma->checkPermissions('payments');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['customer']  = $this->reports_model->getCustomerCompanies();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $this->data['paymentcount'] = $this->getCountPayment();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('payments_report')));
        $meta = array('page_title' => lang('payments_report'), 'bc' => $bc);
        $this->page_construct('reports/payments', $meta, $this->data);
    }

    function getPaymentsReport($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('payments', TRUE);

        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $supplier = $this->input->get('supplier') ? $this->input->get('supplier') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $biller = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $payment_ref = $this->input->get('payment_ref') ? $this->input->get('payment_ref') : NULL;
        $sale_ref = $this->input->get('sale_ref') ? $this->input->get('sale_ref') : NULL;
        $purchase_ref = $this->input->get('purchase_ref') ? $this->input->get('purchase_ref') : NULL;
        $card = $this->input->get('card') ? $this->input->get('card') : NULL;
        $cheque = $this->input->get('cheque') ? $this->input->get('cheque') : NULL;
        $transaction_id = $this->input->get('tid') ? $this->input->get('tid') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $strtlimit = $this->input->get('strtlimit') ? $this->input->get('strtlimit') : NULL;
        $limit = explode("-", $strtlimit);
        $start = $limit['0'];
        $limit = $limit['1'];
        /*
          if($start_date)
          {
          $start_date = $this->sma->fld($start_date);
          $end_date = $this->sma->fld($end_date);
          } */
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }
        if ($pdf || $xls || $img) {

            $this->db->select("" . $this->db->dbprefix('payments') . ".date, " . $this->db->dbprefix('payments') . ".reference_no as payment_ref, " . $this->db->dbprefix('sales') . ".reference_no as sale_ref,, " . $this->db->dbprefix('sales') . ".invoice_no as invoice_no, " . $this->db->dbprefix('purchases') . ".reference_no as purchase_ref, " . $this->db->dbprefix('orders') . ".reference_no as order_ref, paid_by, amount, type, " . $this->db->dbprefix('warehouses') . ".name as warehouse_name,  CONCAT(" . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . ".last_name) as user_name")->from('payments')->join('users', 'users.id=payments.created_by', 'left')->join('sales', 'payments.sale_id=sales.id', 'left')->join('orders', 'payments.order_id=orders.id', 'left')->join('warehouses', 'sales.warehouse_id=warehouses.id', 'left')->join('purchases', 'payments.purchase_id=purchases.id', 'left')->group_by('payments.id')->order_by('payments.date desc');

            if ($user) {
                $this->db->where('payments.created_by', $user);
            }
            if ($card) {
                $this->db->like('payments.cc_no', $card, 'both');
            }
            if ($cheque) {
                $this->db->where('payments.cheque_no', $cheque);
            }
            if ($transaction_id) {
                $this->db->where('payments.transaction_id', $transaction_id);
            }

            if ($customer) {
                $this->db->where('sales.customer_id', $customer);
            }
            if ($supplier) {
                $this->db->where('purchases.supplier_id', $supplier);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('sales.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($biller) {
                $this->db->where('sales.biller_id', $biller);
            }
            if ($customer) {
                $this->db->where('sales.customer_id', $customer);
            }
            if ($payment_ref) {
                $this->db->like('payments.reference_no', $payment_ref, 'both');
            }
            if ($sale_ref) {
                $this->db->like('sales.reference_no', $sale_ref, 'both');
            }
            if ($purchase_ref) {
                $this->db->like('purchases.reference_no', $purchase_ref, 'both');
            }
            if ($start_date) {
                // $this->db->where($this->db->dbprefix('payments') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('payments') . '.date) >= "' . $start_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('payments') . '.date) <= "' . $end_date . '"');
            }
            if ($limit != '' && $start != '') {
                $this->db->limit($limit, $start);
            }
            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:K1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:K1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Payments Report');
                $this->excel->getActiveSheet()->setTitle(lang('payments_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('payment_reference'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('sale_reference'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Invoice_no'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('purchase_reference'));
				$this->excel->getActiveSheet()->SetCellValue('F2', lang('Order_reference'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('paid_by'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('amount'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('type'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Warehouse'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('User'));
                $row = 3;
                $total = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->payment_ref);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->sale_ref);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->invoice_no);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->purchase_ref);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->order_ref);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, lang($data_row->paid_by));
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->amount);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->type);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row->warehouse_name);
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $data_row->user_name);
                    /* if($data_row->type == 'sent')
                      { //$data_row->type == 'returned' ||
                      $total -= $data_row->amount;
                      }else
                      {
                      $total += $data_row->amount;
                      } */
                    $total += $data_row->amount;
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $total);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(15);

                $filename = 'payments_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/payments_report.pdf', __FILE__));
                    redirect("reports/create_image/payments_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            //REPLACE({$this->db->dbprefix('sales')}.reference_no, SUBSTRING_INDEX({$this->db->dbprefix('sales')}.reference_no, '/', -1), {$this->db->dbprefix('sales')}.id)
            $this->load->library('datatables');
            $this->datatables->select("DATE_FORMAT({$this->db->dbprefix('payments')}.date, '%Y-%m-%d %T') as date, " . $this->db->dbprefix('payments') . ".reference_no as payment_ref, " . $this->db->dbprefix('sales') . ".reference_no as sale_ref, " . $this->db->dbprefix('sales') . ".invoice_no as invoice_no, " . $this->db->dbprefix('purchases') . ".reference_no as purchase_ref, " . $this->db->dbprefix('orders') . ".reference_no as order_ref, paid_by, amount, type, {$this->db->dbprefix('payments')}.id as id")
                    ->from('payments')
                    ->join('sales', 'payments.sale_id=sales.id', 'left')
                    ->join('orders', 'payments.order_id=orders.id', 'left')
                    ->join('purchases', 'payments.purchase_id=purchases.id', 'left')
                    ->group_by('payments.id');

            if ($user) {
                $this->datatables->where('payments.created_by', $user);
            }
            if ($card) {
                $this->datatables->like('payments.cc_no', $card, 'both');
            }
            if ($cheque) {
                $this->datatables->where('payments.cheque_no', $cheque);
            }
            if ($transaction_id) {
                $this->datatables->where('payments.transaction_id', $transaction_id);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('sales.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($customer) {
                $this->datatables->where('sales.customer_id', $customer);
            }
            if ($supplier) {
                $this->datatables->where('purchases.supplier_id', $supplier);
            }
            if ($biller) {
                $this->datatables->where('sales.biller_id', $biller);
            }
            if ($customer) {
                $this->datatables->where('sales.customer_id', $customer);
            }
            if ($payment_ref) {
                $this->datatables->like('payments.reference_no', $payment_ref, 'both');
            }
            if ($sale_ref) {
                $this->datatables->like('sales.reference_no', $sale_ref, 'both');
            }
            if ($purchase_ref) {
                $this->datatables->like('purchases.reference_no', $purchase_ref, 'both');
            }
            if ($start_date) {
                // $this->db->where($this->db->dbprefix('payments') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('payments') . '.date) >= "' . $start_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('payments') . '.date) <= "' . $end_date . '"');
            }
            echo $this->datatables->generate();
        }
    }

    /** Payment Summary */
    public function paymentssummary() {
        $this->sma->checkPermissions('paymentssummary');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Payments Summary')));
        $meta = array('page_title' => lang('Payments Summary'), 'bc' => $bc);
        $this->page_construct('reports/payments_summary', $meta, $this->data);
    }

    /**
     * 
     * @param type $pdf
     * @param type $xls
     */
    public function getPaymentSummary($pdf = NULL, $xls = NULL) {


        $this->sma->checkPermissions('payments', TRUE);

        $paymenttype = $this->input->get('paymenttype') ? $this->input->get('paymenttype') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $uses = $this->input->get('user') ? $this->input->get('user') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        // Get Active payment Option
        $payment_option = $this->reports_model->payment_option();
        $option_length = sizeof($payment_option);

        if ($pdf || $xls) {
            // Collect Datewise total amount
            $summarydata = $this->reports_model->payment_summary($start_date, $end_date, $paymenttype, $uses, $warehouse);



            if (!empty($summarydata)) {
                // Using Column 
                $alpha = array('C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $this->excel->getActiveSheet()->setTitle(lang('Payment Summary'));
                $this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B1', lang('Type'));

                // Active Payment Option
                foreach ($payment_option as $key => $option) {
                    $this->excel->getActiveSheet()->SetCellValue($alpha[$key] . '1', ucfirst($option));
                    $totaloption[$key] = 0; // Create Varible uisng payment option total
                }


                $this->excel->getActiveSheet()->SetCellValue($alpha[$option_length] . '1', lang('Total Amount'));

                $row = 2;
                $total = 0;
                $totalreciived = 0;
                $totalsent = 0;
                foreach ($summarydata as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, date('d-m-Y', strtotime($data_row->date)));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, ($data_row->type) ? $data_row->type : '---');
                    // Column bind paymet option
                    foreach ($payment_option as $key => $option) {

                        $retrun_option = $this->reports_model->getoptionpayment($option, date('y-m-d', strtotime($data_row->date)), $data_row->type, $uses, $warehouse);
                        $this->excel->getActiveSheet()->SetCellValue($alpha[$key] . $row, ($retrun_option->$option) ? $retrun_option->$option : '---');
                        $totaloption[$key] += $retrun_option->$option;
                    }


                    $this->excel->getActiveSheet()->SetCellValue($alpha[$option_length] . $row, $data_row->Total);


                    $total += $data_row->Total;

                    $row++;
                }

                $this->excel->getActiveSheet()->getStyle("A" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue("A" . $row, "Total");


                foreach ($payment_option as $key => $option) {
                    $this->excel->getActiveSheet()->getStyle($alpha[$key] . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $this->excel->getActiveSheet()->SetCellValue($alpha[$key] . $row, $totaloption[$key]);
                }


                $this->excel->getActiveSheet()->getStyle($alpha[$option_length] . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue($alpha[$option_length] . $row, $total);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                foreach ($payment_option as $key => $option) {
                    $this->excel->getActiveSheet()->getColumnDimension($alpha[$key])->setWidth(25);
                }

                $this->excel->getActiveSheet()->getColumnDimension($alpha[$option_length])->setWidth(25);

                $filename = 'payments_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }

                if ($xls) {
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
            }

            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');

            $this->datatables->select(' DATE_FORMAT(sma_payments.date, "%Y-%m-%d") as date, sum(sma_payments.amount) as Total, sma_payments.type');

            foreach ($payment_option as $option) {

                $this->datatables->add_column($option, '');
            }

            $this->datatables->add_column('Total', '00', '');

            $this->datatables->from('sma_payments');

            if ($start_date && $end_date) {
                $this->datatables->where('sma_payments.date ' . ' BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            if (isset($paymenttype)) {
                $this->datatables->where('sma_payments.type', $paymenttype);
            }

            if (isset($uses)) {
                $this->datatables->where('sma_payments.created_by', $uses);
            }

            if (isset($warehouse)) {
                $this->datatables->join('sma_sales', 'sma_sales.id = sma_payments.sale_id');
                $this->datatables->where('sma_sales.warehouse_id', $warehouse);
            }

            $this->datatables->group_by('DATE_FORMAT(sma_payments.date, "%Y-%m-%d"),sma_payments.type');


            echo $this->datatables->generate();
        }
    }

    /**
     * @return json
     */
    public function getpaidamount() {
        $datevalue = $this->input->get('date');
        $option = $this->input->get('option');
        $type = $this->input->get('type');
        $users = ($this->input->get('user')) ? $this->input->get('user') : null;
        $warehouse = ($this->input->get('warehouse')) ? $this->input->get('warehouse') : null;

        $retrun_option = $this->reports_model->getoptionpayment($option, date('Y-m-d', strtotime($datevalue)), $type, $users, $warehouse);

        $response['success'] = true;
        $response['data'] = ($retrun_option->$option) ? $retrun_option->$option : '0';
        echo json_encode($response);
    }

    /**
     * @return json
     */
    public function getpaidtotal() {
        $option = $this->input->get('option');
        $type = ($this->input->get('paymenttype')) ? $this->input->get('paymenttype') : null;
        $start_date = ($this->input->get('start_date')) ? $this->input->get('start_date') : null;
        $end_date = ($this->input->get('end_date')) ? $this->input->get('end_date') : null;
        $users = ($this->input->get('user')) ? $this->input->get('user') : null;
        $warehouse = ($this->input->get('warehouse')) ? $this->input->get('warehouse') : null;


        $retrun_option = $this->reports_model->getTotal($option, $type, $start_date, $end_date, $users, $warehouse);

        $response['success'] = true;
        $response['data'] = ($retrun_option->$option) ? $retrun_option->$option : '0';
        echo json_encode($response);
    }

    /** End Payment Summary * */
    /* Count Payment report */
    public function getCountPayment() {
        $this->sma->checkPermissions('payments', TRUE);
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $supplier = $this->input->get('supplier') ? $this->input->get('supplier') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $biller = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $payment_ref = $this->input->get('payment_ref') ? $this->input->get('payment_ref') : NULL;
        $sale_ref = $this->input->get('sale_ref') ? $this->input->get('sale_ref') : NULL;
        $purchase_ref = $this->input->get('purchase_ref') ? $this->input->get('purchase_ref') : NULL;
        $card = $this->input->get('card') ? $this->input->get('card') : NULL;
        $cheque = $this->input->get('cheque') ? $this->input->get('cheque') : NULL;
        $transaction_id = $this->input->get('tid') ? $this->input->get('tid') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }
        $this->db->select("" . $this->db->dbprefix('payments') . ".date, " . $this->db->dbprefix('payments') . ".reference_no as payment_ref, " . $this->db->dbprefix('sales') . ".reference_no as sale_ref, " . $this->db->dbprefix('purchases') . ".reference_no as purchase_ref, paid_by, amount, type")->from('payments')->join('sales', 'payments.sale_id=sales.id', 'left')->join('purchases', 'payments.purchase_id=purchases.id', 'left')->group_by('payments.id')->order_by('payments.date desc');

        if ($user) {
            $this->db->where('payments.created_by', $user);
        }
        if ($card) {
            $this->db->like('payments.cc_no', $card, 'both');
        }
        if ($cheque) {
            $this->db->where('payments.cheque_no', $cheque);
        }
        if ($transaction_id) {
            $this->db->where('payments.transaction_id', $transaction_id);
        }
        if ($customer) {
            $this->db->where('sales.customer_id', $customer);
        }
        if ($supplier) {
            $this->db->where('purchases.supplier_id', $supplier);
        }
        if ($biller) {
            $this->db->where('sales.biller_id', $biller);
        }
        if ($customer) {
            $this->db->where('sales.customer_id', $customer);
        }
        if ($payment_ref) {
            $this->db->like('payments.reference_no', $payment_ref, 'both');
        }
        if ($sale_ref) {
            $this->db->like('sales.reference_no', $sale_ref, 'both');
        }
        if ($purchase_ref) {
            $this->db->like('purchases.reference_no', $purchase_ref, 'both');
        }
        if ($start_date) {
            $this->db->where($this->db->dbprefix('payments') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
        }

        $q = $this->db->get();
        if ($q->num_rows() > 0) {
            // foreach(($q->result()) as $row)
            //{
            //$data[] = $row;
            $data = $q->num_rows();
            //}
            return $data;
        }

        return FALSE;
    }

    function customers() {
        $this->sma->checkPermissions('customers');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('customers_report')));
        $meta = array('page_title' => lang('customers_report'), 'bc' => $bc);
        $this->page_construct('reports/customers', $meta, $this->data);
    }

    function getCustomers($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('customers', TRUE);
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        if($start_date)
        {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }  
        if ($pdf || $xls || $img) {

            $this->db->select($this->db->dbprefix('companies') . ".id as id, company, name, phone, email, count(" . $this->db->dbprefix('sales') . ".id) as total, (COALESCE(sum(grand_total), 0)+COALESCE(sum(rounding), 0)) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) + COALESCE(sum(rounding), 0)- COALESCE(sum(paid), 0)) as balance", FALSE)->from("companies")->join('sales', 'sales.customer_id=companies.id')->where('companies.group_name', 'customer')->order_by('companies.company asc')->group_by('companies.id');
             if($start_date)
            {
                //$this->db->where($this->db->dbprefix('sales') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                $this->db->where('DATE('.$this->db->dbprefix('sales') . '.date) >= "' . $start_date.'"');
                $this->db->where('DATE('.$this->db->dbprefix('sales') . '.date) <= "' . $end_date.'"'); 
            }
            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:H1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:H1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Customers Report');
                $this->excel->getActiveSheet()->setTitle(lang('customers_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('company'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('phone'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('email'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('total_sales'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('total_amount'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('balance'));

                $row = 3;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->company);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->phone);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->email);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->total);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $this->sma->formatMoney($data_row->total_amount));
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $this->sma->formatMoney($data_row->paid));
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $this->sma->formatMoney($data_row->balance));
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $filename = 'customers_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/customers.pdf', __FILE__));
                    redirect("reports/create_image/customers.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $s = "( SELECT customer_id, count(" . $this->db->dbprefix('sales') . ".id) as total, (COALESCE(sum(grand_total), 0)+COALESCE(sum(rounding), 0)) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) + COALESCE(sum(rounding), 0)- COALESCE(sum(paid), 0)) as balance from {$this->db->dbprefix('sales')} ";
            if($start_date)
            {
                $s.=" WHERE {$this->db->dbprefix('sales')}.date BETWEEN  '$start_date' and  '$end_date' ";
            }
            $s.="GROUP BY {$this->db->dbprefix('sales')}.customer_id ) FS";


            $this->load->library('datatables');
            $this->datatables->select($this->db->dbprefix('companies') . ".id as id, company, name, phone, email, FS.total, FS.total_amount, FS.paid, FS.balance", FALSE)->from("companies")->join($s, 'FS.customer_id=companies.id')->where('companies.group_name', 'customer')->group_by('companies.id')->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/customer_report/$1') ."/".trim($start_date)."/".$end_date."'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")->unset_column('id');
            echo $this->datatables->generate();
        }
    }

    function customer_report($user_id = NULL,$start_date = Null ,$end_date = Null) {
        $this->sma->checkPermissions('customers', TRUE);
        if (!$user_id) {
            $this->session->set_flashdata('error', lang("no_customer_selected"));
            redirect('reports/customers');
        }

        $this->data['sales'] = $this->reports_model->getSalesTotals($user_id);
        $this->data['total_sales'] = $this->reports_model->getCustomerSales($user_id);
        $this->data['total_quotes'] = $this->reports_model->getCustomerQuotes($user_id);
        $this->data['total_returns'] = $this->reports_model->getCustomerReturns($user_id);
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['billers'] = $this->site->getAllCompanies('biller');

        $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

        $this->data['user_id'] = $user_id;
        if($start_date != Null){
        $this->data['start_date'] = $start_date;
        $this->data['end_date'] = $end_date;
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('customers_report')));
        $meta = array('page_title' => lang('customers_report'), 'bc' => $bc);
        $this->page_construct('reports/customer_report', $meta, $this->data);
    }

    function suppliers() {
        $this->sma->checkPermissions('suppliers');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('suppliers_report')));
        $meta = array('page_title' => lang('suppliers_report'), 'bc' => $bc);
        $this->page_construct('reports/suppliers', $meta, $this->data);
    }

    function getSuppliers($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('suppliers', TRUE);
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        if($start_date)
        {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }

        if ($pdf || $xls || $img) {

            $this->db->select($this->db->dbprefix('companies') . ".id as id, company, name, phone, email, count({$this->db->dbprefix('purchases')}.id) as total, COALESCE(sum(grand_total), 0) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) - COALESCE(sum(paid), 0)) as balance", FALSE)->from("companies")->join('purchases', 'purchases.supplier_id=companies.id')->where('companies.group_name', 'supplier')->order_by('companies.company asc')->group_by('companies.id');
            if($start_date)
            {
                $this->db->where('DATE('.$this->db->dbprefix('purchases') . '.date) >= "' . $start_date.'"');
                $this->db->where('DATE('.$this->db->dbprefix('purchases') . '.date) <= "' . $end_date.'"'); 
            }     
            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:H1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:H1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Suppliers Report');
                $this->excel->getActiveSheet()->setTitle(lang('suppliers_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('company'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('phone'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('email'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('total_purchases'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('total_amount'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('balance'));

                $row = 3;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->company);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->phone);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->email);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->total);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->total_amount);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->paid);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->balance);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $filename = 'suppliers_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/suppliers.pdf', __FILE__));
                    redirect("reports/create_image/suppliers.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

             $p = "( SELECT supplier_id, count(" . $this->db->dbprefix('purchases') . ".id) as total, COALESCE(sum(grand_total), 0) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) - COALESCE(sum(paid), 0)) as balance from {$this->db->dbprefix('purchases')} ";
             if($start_date)
            {
                $p.=" WHERE {$this->db->dbprefix('purchases')}.date BETWEEN  '$start_date' and  '$end_date' ";
            }
            $p.="GROUP BY {$this->db->dbprefix('purchases')}.supplier_id ) FP";  

            $this->load->library('datatables');
            $this->datatables->select($this->db->dbprefix('companies') . ".id as id, company, name, phone, email, FP.total, FP.total_amount, FP.paid, FP.balance", FALSE)->from("companies")->join($p, 'FP.supplier_id=companies.id')->where('companies.group_name', 'supplier')->group_by('companies.id')->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/supplier_report/$1') ."/".trim($start_date)."/".$end_date. "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")->unset_column('id');
            echo $this->datatables->generate();
        }
    }

    function supplier_report($user_id = NULL,$start_date = NULL,$end_date = NULL) {
        $this->sma->checkPermissions('suppliers', TRUE);
        if (!$user_id) {
            $this->session->set_flashdata('error', lang("no_supplier_selected"));
            redirect('reports/suppliers');
        }

        $this->data['purchases'] = $this->reports_model->getPurchasesTotals($user_id);
        $this->data['total_purchases'] = $this->reports_model->getSupplierPurchases($user_id);
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();

        $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

        $this->data['user_id'] = $user_id;
        if($start_date != Null){
        $this->data['start_date'] = $start_date;
        $this->data['end_date'] = $end_date;
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('suppliers_report')));
        $meta = array('page_title' => lang('suppliers_report'), 'bc' => $bc);
        $this->page_construct('reports/supplier_report', $meta, $this->data);
    }

    function users() {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('staff_report')));
        $meta = array('page_title' => lang('staff_report'), 'bc' => $bc);
        $this->page_construct('reports/users', $meta, $this->data);
    }

    function getUsers() {
        $this->load->library('datatables');
        $this->datatables->select($this->db->dbprefix('users') . ".id as id, first_name, last_name, email, company, " . $this->db->dbprefix('groups') . ".name, active")->from("users")->join('groups', 'users.group_id=groups.id', 'left')->group_by('users.id')->where('company_id', NULL);
        if (!$this->Owner) {
            $this->datatables->where('group_id !=', 1);
        }
        $this->datatables->edit_column('active', '$1__$2', 'active, id')->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/staff_report/$1') . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")->unset_column('id');
        echo $this->datatables->generate();
    }

    function user_actions() {
        if (!$this->Owner) {
            $this->session->set_flashdata('warning', lang('access_denied'));
            redirect($_SERVER["HTTP_REFERER"]);
        }
        $user = $this->site->getAllUser();

        $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

        if ($this->form_validation->run() == true) {
            if ($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:F1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:F1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Staff Reports');
                $this->excel->getActiveSheet()->setTitle(lang('users'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('first_name'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('last_name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('email'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('company'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('group'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('status'));

                $row = 3;


                foreach ($user as $users) {

                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $users['first_name']);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $users['last_name']);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $users['email']);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $users['company']);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $users['name']);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, ($users['active'] == 1) ? 'Active' : 'Inactive');
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $filename = 'users_' . date('Y_m_d_H_i_s');
                if ($this->input->post('form_action') == 'export_pdf') {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
                                PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    return $objWriter->save('php://output');
                }
                if ($this->input->post('form_action') == 'export_excel') {
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    return $objWriter->save('php://output');
                }

                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    function staff_report($user_id = NULL, $year = NULL, $month = NULL, $pdf = NULL, $cal = 0) {

        if (!$user_id) {
            $this->session->set_flashdata('error', lang("no_user_selected"));
            redirect('reports/users');
        }
        $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
        $this->data['purchases'] = $this->reports_model->getStaffPurchases($user_id);
        $this->data['sales'] = $this->reports_model->getStaffSales($user_id);
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $this->data['warehouses'] = $this->site->getAllWarehouses();

        if (!$year) {
            $year = date('Y');
        }
        if (!$month || $month == '#monthly-con') {
            $month = date('m');
        }
        if ($pdf) {
            if ($cal) {
                $this->monthly_sales(NULL, $year, $pdf, $user_id);
            } else {
                $this->daily_sales(NULL, $year, $month, $pdf, $user_id);
            }
        }
        $config = array('show_next_prev' => TRUE, 'next_prev_url' => site_url('reports/staff_report/' . $user_id), 'month_type' => 'long', 'day_type' => 'long');

        $config['template'] = '{table_open}<div class="table-responsive"><table border="0" cellpadding="0" cellspacing="0" class="table table-bordered dfTable reports-table">{/table_open}
		{heading_row_start}<tr>{/heading_row_start}
		{heading_previous_cell}<th class="text-center"><a href="{previous_url}">&lt;&lt;</a></th>{/heading_previous_cell}
		{heading_title_cell}<th class="text-center" colspan="{colspan}" id="month_year">{heading}</th>{/heading_title_cell}
		{heading_next_cell}<th class="text-center"><a href="{next_url}">&gt;&gt;</a></th>{/heading_next_cell}
		{heading_row_end}</tr>{/heading_row_end}
		{week_row_start}<tr>{/week_row_start}
		{week_day_cell}<td class="cl_wday">{week_day}</td>{/week_day_cell}
		{week_row_end}</tr>{/week_row_end}
		{cal_row_start}<tr class="days">{/cal_row_start}
		{cal_cell_start}<td class="day">{/cal_cell_start}
		{cal_cell_content}
		<div class="day_num">{day}</div>
		<div class="content">{content}</div>
		{/cal_cell_content}
		{cal_cell_content_today}
		<div class="day_num highlight">{day}</div>
		<div class="content">{content}</div>
		{/cal_cell_content_today}
		{cal_cell_no_content}<div class="day_num">{day}</div>{/cal_cell_no_content}
		{cal_cell_no_content_today}<div class="day_num highlight">{day}</div>{/cal_cell_no_content_today}
		{cal_cell_blank}&nbsp;{/cal_cell_blank}
		{cal_cell_end}</td>{/cal_cell_end}
		{cal_row_end}</tr>{/cal_row_end}
		{table_close}</table></div>{/table_close}';

        $this->load->library('calendar', $config);
        $sales = $this->reports_model->getStaffDailySales($user_id, $year, $month);

        if (!empty($sales)) {
            foreach ($sales as $sale) {
                $daily_sale[$sale->date] = "<table class='table table-bordered table-hover table-striped table-condensed data' style='margin:0;'><tr><td>" . lang("discount") . "</td><td>" . $this->sma->formatMoney($sale->discount) . "</td></tr><tr><td>" . lang("product_tax") . "</td><td>" . $this->sma->formatMoney($sale->tax1) . "</td></tr><tr><td>" . lang("order_tax") . "</td><td>" . $this->sma->formatMoney($sale->tax2) . "</td></tr><tr><td>" . lang("total") . "</td><td>" . $this->sma->formatMoney($sale->total) . "</td></tr></table>";
            }
        } else {
            $daily_sale = array();
        }
        $this->data['calender'] = $this->calendar->generate($year, $month, $daily_sale);
        if ($this->input->get('pdf')) {
            
        }
        $this->data['year'] = $year;
        $this->data['month'] = $month;
        $this->data['msales'] = $this->reports_model->getStaffMonthlySales($user_id, $year);
        $this->data['user_id'] = $user_id;
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('staff_report')));
        $meta = array('page_title' => lang('staff_report'), 'bc' => $bc);
        $this->page_construct('reports/staff_report', $meta, $this->data);
    }

    function getUserLogins($id = NULL, $pdf = NULL, $xls = NULL) {
        if ($this->input->get('start_date')) {
            $login_start_date = $this->input->get('start_date');
        } else {
            $login_start_date = NULL;
        }
        if ($this->input->get('end_date')) {
            $login_end_date = $this->input->get('end_date');
        } else {
            $login_end_date = NULL;
        }
        if ($login_start_date) {
            $login_start_date = $this->sma->fld($login_start_date);
            $login_end_date = $login_end_date ? $this->sma->fld($login_end_date) : date('Y-m-d H:i:s');
        }
        if ($pdf || $xls) {

            $this->db->select("login, ip_address, time")->from("user_logins")->where('user_id', $id)->order_by('time desc');
            if ($login_start_date) {
                $this->db->where("time BETWEEN '{$login_start_date}' and '{$login_end_date}'", NULL, FALSE);
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $this->excel->getActiveSheet()->setTitle(lang('staff_login_report'));
                $this->excel->getActiveSheet()->SetCellValue('A1', lang('email'));
                $this->excel->getActiveSheet()->SetCellValue('B1', lang('ip_address'));
                $this->excel->getActiveSheet()->SetCellValue('C1', lang('time'));

                $row = 2;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->login);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->ip_address);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $this->sma->hrld($data_row->time));
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(35);

                $filename = 'staff_login_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    $this->excel->getActiveSheet()->getStyle('C2:C' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            $this->datatables->select("login, ip_address, DATE_FORMAT(time, '%Y-%m-%d %T') as time")->from("user_logins")->where('user_id', $id);
            if ($login_start_date) {
                $this->datatables->where("time BETWEEN '{$login_start_date}' and '{$login_end_date}'", NULL, FALSE);
            }
            echo $this->datatables->generate();
        }
    }

    function getCustomerLogins($id = NULL) {
        if ($this->input->get('login_start_date')) {
            $login_start_date = $this->input->get('login_start_date');
        } else {
            $login_start_date = NULL;
        }
        if ($this->input->get('login_end_date')) {
            $login_end_date = $this->input->get('login_end_date');
        } else {
            $login_end_date = NULL;
        }
        if ($login_start_date) {
            $login_start_date = $this->sma->fld($login_start_date);
            $login_end_date = $login_end_date ? $this->sma->fld($login_end_date) : date('Y-m-d H:i:s');
        }
        $this->load->library('datatables');
        $this->datatables->select("login, ip_address, time")->from("user_logins")->where('customer_id', $id);
        if ($login_start_date) {
            $this->datatables->where('time BETWEEN "' . $login_start_date . '" and "' . $login_end_date . '"');
        }
        echo $this->datatables->generate();
    }

    function profit_loss($start_date = NULL, $end_date = NULL) {
        $this->sma->checkPermissions('profit_loss');

        if (!$start_date) {
            $start = $this->db->escape(date('Y-m') . '-1');
            $start_date = date('Y-m') . '-1';
        } else {
            $start = $this->db->escape(urldecode($start_date));
        }
        if (!$end_date) {
            $end = $this->db->escape(date('Y-m-d H:i'));
            $end_date = date('Y-m-d H:i');
        } else {
            $end = $this->db->escape(urldecode($end_date));
        }

        $sDate = date("Y-m-d H:i:s", strtotime(urldecode($start_date)));
        $eDate = date("Y-m-d H:i:s", strtotime(urldecode($end_date)));
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $this->data['total_purchases'] = $this->reports_model->getTotalPurchases($start, $end);
        $this->data['total_sales'] = $this->reports_model->getTotalSales($start, $end);
        $this->data['total_expenses'] = $this->reports_model->getTotalExpenses($start, $end);
        $this->data['total_paid'] = $this->reports_model->getTotalPaidAmount($start, $end);
        $this->data['total_received'] = $this->reports_model->getTotalReceivedAmount($start, $end);
        $this->data['total_received_cash'] = $this->reports_model->getTotalReceivedCashAmount($start, $end);
        $this->data['total_received_cc'] = $this->reports_model->getTotalReceivedCCAmount($start, $end);
        $this->data['total_received_cheque'] = $this->reports_model->getTotalReceivedChequeAmount($start, $end);
        $this->data['total_received_ppp'] = $this->reports_model->getTotalReceivedPPPAmount($start, $end);
        $this->data['total_received_stripe'] = $this->reports_model->getTotalReceivedStripeAmount($start, $end);
        $this->data['total_returned'] = $this->reports_model->getTotalReturnedAmount($start, $end);
        $this->data['start'] = urldecode($start_date);
        $this->data['end'] = urldecode($end_date);

        $warehouses = $this->site->getAllWarehouses();
        foreach ($warehouses as $warehouse) {
            $total_purchases = $this->reports_model->getTotalPurchases($start, $end, $warehouse->id);
            $total_sales = $this->reports_model->getTotalSales($start, $end, $warehouse->id);
            $total_expenses = $this->reports_model->getTotalExpenses($start, $end, $warehouse->id);
            $warehouses_report[] = array('warehouse' => $warehouse, 'total_purchases' => $total_purchases, 'total_sales' => $total_sales, 'total_expenses' => $total_expenses,);
        }
        $this->data['warehouses_report'] = $warehouses_report;

        $param = array();
        $param['start_date'] = $start ? $sDate : NULL;
        $param['end_date'] = $end ? $eDate : NULL;

        $this->data['taxReportSales'] = $this->reports_model->salesTaxReport($param);
        $this->data['taxReportPurchases'] = $this->reports_model->purchaseTaxReport($param);

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('profit_loss')));
        $meta = array('page_title' => lang('profit_loss'), 'bc' => $bc);
        $this->page_construct('reports/profit_loss', $meta, $this->data);
    }

    function profit_loss_pdf($start_date = NULL, $end_date = NULL) {
        $this->sma->checkPermissions('profit_loss');
        if (!$start_date) {
            $start = $this->db->escape(date('Y-m') . '-1');
            $start_date = date('Y-m') . '-1';
        } else {
            $start = $this->db->escape(urldecode($start_date));
        }
        if (!$end_date) {
            $end = $this->db->escape(date('Y-m-d H:i'));
            $end_date = date('Y-m-d H:i');
        } else {
            $end = $this->db->escape(urldecode($end_date));
        }

        $sDate = date("Y-m-d H:i:s", strtotime(urldecode($start_date)));
        $eDate = date("Y-m-d H:i:s", strtotime(urldecode($end_date)));

        $this->data['total_purchases'] = $this->reports_model->getTotalPurchases($start, $end);
        $this->data['total_sales'] = $this->reports_model->getTotalSales($start, $end);
        $this->data['total_expenses'] = $this->reports_model->getTotalExpenses($start, $end);
        $this->data['total_paid'] = $this->reports_model->getTotalPaidAmount($start, $end);
        $this->data['total_received'] = $this->reports_model->getTotalReceivedAmount($start, $end);
        $this->data['total_received_cash'] = $this->reports_model->getTotalReceivedCashAmount($start, $end);
        $this->data['total_received_cc'] = $this->reports_model->getTotalReceivedCCAmount($start, $end);
        $this->data['total_received_cheque'] = $this->reports_model->getTotalReceivedChequeAmount($start, $end);
        $this->data['total_received_ppp'] = $this->reports_model->getTotalReceivedPPPAmount($start, $end);
        $this->data['total_received_stripe'] = $this->reports_model->getTotalReceivedStripeAmount($start, $end);
        $this->data['total_returned'] = $this->reports_model->getTotalReturnedAmount($start, $end);
        $this->data['start'] = urldecode($start_date);
        $this->data['end'] = urldecode($end_date);
        $param = array();
        $param['start_date'] = $start ? $sDate : NULL;
        $param['end_date'] = $end ? $eDate : NULL;

        $this->data['taxReportSales'] = $this->reports_model->salesTaxReport($param);
        $this->data['taxReportPurchases'] = $this->reports_model->purchaseTaxReport($param);

        $warehouses = $this->site->getAllWarehouses();
        foreach ($warehouses as $warehouse) {
            $total_purchases = $this->reports_model->getTotalPurchases($start, $end, $warehouse->id);
            $total_sales = $this->reports_model->getTotalSales($start, $end, $warehouse->id);
            $warehouses_report[] = array('warehouse' => $warehouse, 'total_purchases' => $total_purchases, 'total_sales' => $total_sales,);
        }
        $this->data['warehouses_report'] = $warehouses_report;

        $html = $this->load->view($this->theme . 'reports/profit_loss_pdf', $this->data, TRUE);
        $name = lang("profit_loss") . "-" . str_replace(array('-', ' ', ':'), '_', $this->data['start']) . "-" . str_replace(array('-', ' ', ':'), '_', $this->data['end']) . ".pdf";
        $this->sma->generate_pdf($html, $name, FALSE, FALSE, FALSE, FALSE, FALSE, 'L');
    }

    function register() {
        $this->sma->checkPermissions('register');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('register_report')));
        $meta = array('page_title' => lang('register_report'), 'bc' => $bc);
        $this->page_construct('reports/register', $meta, $this->data);
    }

    function getRrgisterlogs($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('register', TRUE);
        if ($this->input->get('user')) {
            $user = $this->input->get('user');
        } else {
            $user = NULL;
        }
        if ($this->input->get('start_date')) {
            $start_date = $this->input->get('start_date');
        } else {
            $start_date = NULL;
        }
        if ($this->input->get('end_date')) {
            $end_date = $this->input->get('end_date');
        } else {
            $end_date = NULL;
        }
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }

        if ($pdf || $xls || $img) {

            $this->db->select("date, closed_at, CONCAT(" . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . ".last_name, ' (', users.email, ')') as user, cash_in_hand, total_cc_slips, total_cheques, total_cash, total_cc_slips_submitted, total_cheques_submitted,total_cash_submitted, note", FALSE)->from("pos_register")->join('users', 'users.id=pos_register.user_id', 'left')->order_by('date desc');
            //->where('status', 'close');

            if ($user) {
                $this->db->where('pos_register.user_id', $user);
            }
            if ($start_date) {
                $this->db->where('DATE(date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:K1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:K1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Register Report');
                $this->excel->getActiveSheet()->setTitle(lang('register_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('open_time'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('close_time'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('user'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('cash_in_hand'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('cc_slips'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('cheques'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('total_cash'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('cc_slips_submitted'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('cheques_submitted'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('total_cash_submitted'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('note'));

                $row = 3;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->closed_at);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->user);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->cash_in_hand);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->total_cc_slips);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->total_cheques);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->total_cash);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->total_cc_slips_submitted);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->total_cheques_submitted);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row->total_cash_submitted);
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $data_row->note);
                    if ($data_row->total_cash_submitted < $data_row->total_cash || $data_row->total_cheques_submitted < $data_row->total_cheques || $data_row->total_cc_slips_submitted < $data_row->total_cc_slips) {
                        $this->excel->getActiveSheet()->getStyle('A' . $row . ':K' . $row)->applyFromArray(array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'F2DEDE'))));
                    }
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(35);
                $filename = 'register_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    //$this->excel->getActiveSheet()->getStyle('C2:C' . $row)->getAlignment()->setWrapText(true);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/register_report.pdf', __FILE__));
                    redirect("reports/create_image/register_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            $this->datatables->select("date, closed_at, CONCAT(" . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . ".last_name, '<br>', " . $this->db->dbprefix('users') . ".email) as user, cash_in_hand, CONCAT(total_cc_slips, ' (', total_cc_slips_submitted, ')'), CONCAT(total_cheques, ' (', total_cheques_submitted, ')'), CONCAT(total_cash, ' (', total_cash_submitted, ')'), note", FALSE)->from("pos_register")->join('users', 'users.id=pos_register.user_id', 'left');

            if ($user) {
                $this->datatables->where('pos_register.user_id', $user);
            }
            if ($start_date) {
                $this->datatables->where('DATE(date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    public function expenses($id = NULL) {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['categories'] = $this->reports_model->getExpenseCategories();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('expenses')));
        $meta = array('page_title' => lang('expenses'), 'bc' => $bc);
        $this->page_construct('reports/expenses', $meta, $this->data);
    }

    public function getExpensesReport($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('expenses');

        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $category = $this->input->get('category') ? $this->input->get('category') : NULL;
        $note = $this->input->get('note') ? $this->input->get('note') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }

        if ($pdf || $xls || $img) {

            $this->db->select("date, reference, {$this->db->dbprefix('expense_categories')}.name as category, amount, note, {$this->db->dbprefix('warehouses') }.name as wname, CONCAT({$this->db->dbprefix('users')}.first_name, ' ', {$this->db->dbprefix('users')}.last_name) as user, attachment, {$this->db->dbprefix('expenses')}.id as id", FALSE)->from('expenses')->join('users', 'users.id=expenses.created_by', 'left')->join('expense_categories', 'expense_categories.id=expenses.category_id', 'left')->join('warehouses', 'warehouses.id=expenses.warehouse_id', 'left')->group_by('expenses.id');

            if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
                $this->db->where('created_by', $this->session->userdata('user_id'));
            }

            if ($note) {
                $this->db->like('note', $note, 'both');
            }
            if ($reference_no) {
                $this->db->like('reference', $reference_no, 'both');
            }
            if ($category) {
                $this->db->where('category_id', $category);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('expenses.warehouse_id IN (' . $getwarehouse . ')');
            }

            if ($user) {
                $this->db->where('created_by', $user);
            }
            if ($start_date) {
                $this->db->where('DATE(date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            $q = $this->db->get();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $data[] = $row;
                }
            } else {
                $data = NULL;
            }

            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:G1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:G1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Expenses Report');

                $this->excel->getActiveSheet()->setTitle(lang('expenses_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('category'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('amount'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('note'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('warehouse'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('created_by'));

                $row = 3;
                $total = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->category);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->amount);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->note);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->wname);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->user);
                    $total += $data_row->amount;
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("D" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $total);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);

                $filename = 'expenses_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($xls) {
                    //$this->excel->getActiveSheet()->getStyle('C2:C' . $row)->getAlignment()->setWrapText(true);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($img) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                    if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/expense.pdf', __FILE__));
                    redirect("reports/create_image/expense.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            $this->datatables->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference, {$this->db->dbprefix('expense_categories')}.name as category, amount, note, {$this->db->dbprefix('warehouses')}.name as wname, CONCAT({$this->db->dbprefix('users')}.first_name, ' ', {$this->db->dbprefix('users')}.last_name) as user, attachment, {$this->db->dbprefix('expenses')}.id as id", FALSE)->from('expenses')->join('users', 'users.id=expenses.created_by', 'left')->join('expense_categories', 'expense_categories.id=expenses.category_id', 'left')->join('warehouses', 'warehouses.id=expenses.warehouse_id', 'left')->group_by('expenses.id');

            if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
                $this->datatables->where('created_by', $this->session->userdata('user_id'));
            }

            if ($note) {
                $this->datatables->like('note', $note, 'both');
            }
            if ($reference_no) {
                $this->datatables->like('reference', $reference_no, 'both');
            }
            if ($category) {
                $this->datatables->where('category_id', $category);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('expenses.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($user) {
                $this->datatables->where('created_by', $user);
            }
            if ($start_date) {
                $this->datatables->where('DATE(date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function daily_purchases($warehouse_id = NULL, $year = NULL, $month = NULL, $pdf = NULL, $user_id = NULL) {
        $this->sma->checkPermissions();
        if ($warehouse_id != NULL) {
            $warehouse_id = $warehouse_id;
        } else if (!$this->Owner && !$this->Admin && $this->session->userdata('warehouse_id')) {
            $warehouse_id = str_replace(",", "_", $this->session->userdata('warehouse_id'));
        }
        if (!$year) {
            $year = date('Y');
        }
        if (!$month) {
            $month = date('m');
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->data['sel_warehouse'] = $warehouse_id ? (strpos($warehouse_id, '_') !== false) ? NULL : $this->site->getWarehouseByID($warehouse_id) : NULL;

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $config = array('show_next_prev' => TRUE, 'next_prev_url' => site_url('reports/daily_purchases/' . ($this->data['sel_warehouse'] ? $this->data['sel_warehouse']->id : 0)), 'month_type' => 'long', 'day_type' => 'long');

        $config['template'] = '{table_open}<div class="table-responsive"><table border="0" cellpadding="0" cellspacing="0" class="table table-bordered dfTable">{/table_open}
        {heading_row_start}<tr>{/heading_row_start}
        {heading_previous_cell}<th><a href="{previous_url}">&lt;&lt;</a></th>{/heading_previous_cell}
        {heading_title_cell}<th colspan="{colspan}" id="month_year">{heading}</th>{/heading_title_cell}
        {heading_next_cell}<th><a href="{next_url}">&gt;&gt;</a></th>{/heading_next_cell}
        {heading_row_end}</tr>{/heading_row_end}
        {week_row_start}<tr>{/week_row_start}
        {week_day_cell}<td class="cl_wday">{week_day}</td>{/week_day_cell}
        {week_row_end}</tr>{/week_row_end}
        {cal_row_start}<tr class="days">{/cal_row_start}
        {cal_cell_start}<td class="day">{/cal_cell_start}
        {cal_cell_content}
        <div class="day_num">{day}</div>
        <div class="content">{content}</div>
        {/cal_cell_content}
        {cal_cell_content_today}
        <div class="day_num highlight">{day}</div>
        <div class="content">{content}</div>
        {/cal_cell_content_today}
        {cal_cell_no_content}<div class="day_num">{day}</div>{/cal_cell_no_content}
        {cal_cell_no_content_today}<div class="day_num highlight">{day}</div>{/cal_cell_no_content_today}
        {cal_cell_blank}&nbsp;{/cal_cell_blank}
        {cal_cell_end}</td>{/cal_cell_end}
        {cal_row_end}</tr>{/cal_row_end}
        {table_close}</table></div>{/table_close}';

        $this->load->library('calendar', $config);
        $purchases = $user_id ? $this->reports_model->getStaffDailyPurchases($user_id, $year, $month, $warehouse_id) : $this->reports_model->getDailyPurchases($year, $month, $warehouse_id);

        if (!empty($purchases)) {
            foreach ($purchases as $purchase) {
                $daily_purchase[$purchase->date] = "<table class='table table-bordered table-hover table-striped table-condensed data' style='margin:0;'><tr><td>" . lang("discount") . "</td><td>" . $this->sma->formatMoney($purchase->discount) . "</td></tr><tr><td>" . lang("shipping") . "</td><td>" . $this->sma->formatMoney($purchase->shipping) . "</td></tr><tr  style='cursor: pointer' onClick='getpurchaseitemstaxes(" . $year . "," . $month . "," . $purchase->date . ")'><td>" . lang("product_tax") . " <i class='fa fa-list-alt' aria-hidden='true'></i></td><td>" . $this->sma->formatMoney($purchase->tax1) . "</td></tr><tr><td>" . lang("order_tax") . "</td><td>" . $this->sma->formatMoney($purchase->tax2) . "</td></tr><tr><td>" . lang("total") . "</td><td>" . $this->sma->formatMoney($purchase->total) . "</td></tr><tr><td>Items</td><td onClick='getpurchaseitems(" . $year . "," . $month . "," . $purchase->date . ")'><i class='fa fa-list-alt' aria-hidden='true'></i></td></tr></table>";
            }
        } else {
            $daily_purchase = array();
        }

        $this->data['calender'] = $this->calendar->generate($year, $month, $daily_purchase);
        $this->data['year'] = $year;
        $this->data['month'] = $month;
        if ($pdf) {
            $purchase_pdf = array();
            foreach ($purchases as $data_row) {
                $purchase_pdf[$data_row->date] = $data_row;
            }
            sort($purchase_pdf);

            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'DDDDDD'))));

            $this->excel->getActiveSheet()->getStyle("A1:G1")->applyFromArray($style);

            $this->excel->getActiveSheet()->mergeCells('A1:G1');
            $this->excel->getActiveSheet()->SetCellValue('A1', lang('Daily Purchases Report ') . date("M-Y", mktime(0, 0, 0, $month, 1, $year)));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('Sr.No'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Date'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Discount'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Shipping'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Product Tax'));
            $this->excel->getActiveSheet()->SetCellValue('F2', lang('Order Tax'));
            $this->excel->getActiveSheet()->SetCellValue('G2', lang('Total'));
            $row = 3;

            $sr = 1;
            foreach ($purchase_pdf as $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sr);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->date . '/' . $month . '/' . $year);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $this->sma->formatMoney($data_row->discount));
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $this->sma->formatMoney($data_row->shipping));
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $this->sma->formatMoney($data_row->tax1));
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $this->sma->formatMoney($data_row->tax2));
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $this->sma->formatMoney($data_row->total));

                $row++;
                $sr++;
            }
            $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
            $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
            $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);

            $this->excel->getActiveSheet()->getStyle("A2:G" . ($row - 1))->applyFromArray($style);

            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $filename = 'daily_purchase_report';

            if ($pdf == 'pdf') {
                $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                require_once(APPPATH . "third_party" . DIRECTORY_SEPARATOR . "MPDF" . DIRECTORY_SEPARATOR . "mpdf.php");
                $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                $rendererLibrary = 'MPDF';
                $rendererLibraryPath = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . $rendererLibrary;
                if (!PHPExcel_Settings::setPdfRenderer($rendererName, $rendererLibraryPath)) {
                    die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' . PHP_EOL . '