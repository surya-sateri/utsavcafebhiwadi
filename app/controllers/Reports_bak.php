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


        if ($warehouse_id) {
            $expwarehouse = explode("_", $warehouse_id);
            if (sizeof($expwarehouse) > 1) {
                $warehousename = 'All Warehouses';
            } else {
                $WDetails = $this->site->getWarehouseByID($expwarehouse[0]);
                $warehousename = $WDetails[$warehouse_id]->name;
            }
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
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Product Quantity Alerts (' . (isset($warehousename) ? $warehousename : 'All Warehouses') . ')');
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
				COALESCE( IF(PCosts.balacneValue,PCosts.balacneValue,(PCosts.balacneQty * sma_products.cost) ), 0 ) as TotalBalance,
				COALESCE( PSales.totalSale, 0 ) as TotalSales,
                (COALESCE( PSales.totalSale, 0 ) - COALESCE( PCosts.totalPurchase, 0 )) as Profit", FALSE)->from('products')->join($sp, 'products.id = PSales.product_id', 'left')->join($pp, 'products.id = PCosts.product_id', 'left'); //->order_by('products.name')

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
				 IF(PCosts.balacneQty,round(PCosts.balacneQty,2),0) as balance_qty , IF(PCosts.balacneValue,PCosts.balacneValue,(round(PCosts.balacneQty,2) * sma_products.cost)) as balance,
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
                    SUM( round(COALESCE( PCosts.totalPurchase, 0 )) ) as TotalPurchase,
                    SUM( round(COALESCE( PSales.totalSale, 0 )) ) as TotalSales,
                    (SUM( round(COALESCE( PSales.totalSale, 0 )) )- SUM( round(COALESCE( PCosts.totalPurchase, 0 )) ) ) as Profit", FALSE)->from('brands')->join($sp, 'brands.id = PSales.brand', 'left')->join($pp, 'brands.id = PCosts.brand', 'left')->group_by('brands.id, brands.name')->order_by('brands.code', 'asc');

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
                    SUM( round(COALESCE( PCosts.totalPurchase, 0 )) ) as TotalPurchase,
                    SUM( round(COALESCE( PSales.totalSale, 0 )) ) as TotalSales,
                    (SUM( round(COALESCE( PSales.totalSale, 0 )) )- SUM( round(COALESCE( PCosts.totalPurchase, 0 )) ) ) as Profit", FALSE)->from('brands')->join($sp, 'brands.id = PSales.brand', 'left')->join($pp, 'brands.id = PCosts.brand', 'left');

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
        $this->data['saleData'] = $this->reports_model->getTodaySales($date, $warehouse_id);
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
                            <?php
                        }
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

            $this->db->select("sales.id as sale_id,sales.date as date,sales.reference_no,sales.invoice_no, sales.biller,sales.note, sales.customer, sma_companies.phone, sales.grand_total, sales.total_discount, sales.paid, sales.rounding, sales.payment_status", FALSE)->from('sales')->join('sale_items', 'sale_items.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');

            /* $this->db->select("sales.id as sale_id,sales.date as date,sales.reference_no, sales.biller, sales.customer, sales.grand_total, sales.paid, sales.rounding,sales.payment_status", FALSE)->from('sales')->group_by('sales.id')->order_by('sales.date desc'); */
            $this->db->join('sma_companies', 'sma_companies.id = sales.customer_id', 'left');

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->db->where('sales.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('sales.created_by', $user);
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
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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
                    $data[$id]['note'] = $row->note;
                    $data[$id]['biller'] = $row->biller;
                    $data[$id]['customer'] = $row->customer;
                    $data[$id]['phone'] = $row->phone;
                    $data[$id]['grand_total'] = $row->grand_total + $row->rounding;
                    $data[$id]['paid'] = $row->paid;
                    $data[$id]['balance'] = $row->grand_total + $row->rounding - $row->paid;
                    $data[$id]['payment_status'] = $row->payment_status;
                    $data[$id]['total_discount'] = $row->total_discount;

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
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['code'] = $SaleItemsRow->product_code;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['brand'] = $SaleItemsRow->brand_name;

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

                $this->excel->getActiveSheet()->getStyle("A1:M1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:M1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Sales Report');
                $this->excel->getActiveSheet()->setTitle(lang('sales_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('invoice_no'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('biller'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('customer'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Phone No'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('product code'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('product'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Brand'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Varient'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('M2', lang('Discount'));
                $this->excel->getActiveSheet()->SetCellValue('N2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('O2', lang('balance'));
                $this->excel->getActiveSheet()->SetCellValue('P2', lang('payment_status'));
                $this->excel->getActiveSheet()->SetCellValue('Q2', lang('Remark'));


                $row = 3;
                $total = 0;
                $paid = 0;
                $balance = 0;
                $discount = 0;
                foreach ($data as $sale_id => $salesdata) {
                    $data_row = (object) $salesdata;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->invoice_no);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->biller);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->customer);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->phone);
                    // $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->iname);
                    $rowitems = $row;
                    if (!empty($data_row->items)) {
                        foreach ($data_row->items as $saleitem_id => $salesItemsData) {
                            $sales_items_data = (object) $salesItemsData;
                            $this->excel->getActiveSheet()->SetCellValue('G' . $row, $sales_items_data->code);
                            $this->excel->getActiveSheet()->SetCellValue('H' . $row, $sales_items_data->name);
                            $this->excel->getActiveSheet()->SetCellValue('I' . $row, $sales_items_data->brand);

                            $this->excel->getActiveSheet()->SetCellValue('J' . $row, $sales_items_data->variantname);
                            $this->excel->getActiveSheet()->SetCellValue('K' . $row, $sales_items_data->quantity);
                            $row++;
                        }//end foreach
                        $this->excel->getActiveSheet()->SetCellValue('L' . $rowitems, $data_row->grand_total);
                        $this->excel->getActiveSheet()->SetCellValue('M' . $rowitems, lang($data_row->total_discount));
                        $this->excel->getActiveSheet()->SetCellValue('N' . $rowitems, $data_row->paid);
                        $this->excel->getActiveSheet()->SetCellValue('O' . $rowitems, ($data_row->grand_total - $data_row->paid));
                        $this->excel->getActiveSheet()->SetCellValue('P' . $rowitems, lang($data_row->payment_status));
                    }//end if.

                    $this->excel->getActiveSheet()->SetCellValue('Q' . $row, $data_row->note);
                    $total += $data_row->grand_total + $data_row->rounding;
                    $paid += $data_row->paid;
                    $balance += ($data_row->grand_total + $data_row->rounding - $data_row->paid);
                    $discount += $data_row->total_discount;
                    //$row++;
                }
                $this->excel->getActiveSheet()->getStyle("L" . $row . ":O" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('L' . $row, $total);
                $this->excel->getActiveSheet()->SetCellValue('M' . $row, $discount);
                $this->excel->getActiveSheet()->SetCellValue('N' . $row, $paid);
                $this->excel->getActiveSheet()->SetCellValue('O' . $row, $balance);


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
                $this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('P')->setWidth(20);

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
            $si = "( SELECT {$this->db->dbprefix('sale_items')}.sale_id, {$this->db->dbprefix('sale_items')}.product_id, {$this->db->dbprefix('sale_items')}.subtotal, {$this->db->dbprefix('sale_items')}.serial_no, GROUP_CONCAT(CONCAT('',{$this->db->dbprefix('sale_items')}.product_name, IF({$this->db->dbprefix('product_variants')}.name <> 'NULL',CONCAT(' (',{$this->db->dbprefix('product_variants')}.name,')'),''), '__', {$this->db->dbprefix('sale_items')}.quantity) SEPARATOR '___') as item_nane from {$this->db->dbprefix('sale_items')} ";
            $si .= "LEFT JOIN {$this->db->dbprefix('product_variants')} ON {$this->db->dbprefix('sale_items')}.option_id = {$this->db->dbprefix('product_variants')}.id";
            if ($product) {
                $si .= " WHERE {$this->db->dbprefix('sale_items')}.product_id = {$product} ";
            }

            $si .= " GROUP BY {$this->db->dbprefix('sale_items')}.sale_id ) FSI";
            $this->load->library('datatables');
//REPLACE(reference_no, SUBSTRING_INDEX(reference_no, '/', -1), {$this->db->dbprefix('sales')}.id) as reference_no
            $this->datatables->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference_no, invoice_no,biller, customer, sma_companies.phone, FSI.item_nane as iname, (grand_total+rounding) as grand_total,total_discount, paid, (grand_total+rounding -paid) as balance, payment_status, {$this->db->dbprefix('sales')}.id as id", FALSE)->from('sales')->join($si, 'FSI.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');
            $this->datatables->join('sma_companies', 'sma_companies.id = sales.customer_id', 'left');
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
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function getSalesReportProduct($pdf = NULL, $xls = NULL, $img = NULL) {
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
                    $this->db->where('sales.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('sales.created_by', $user);
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
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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
            $si = "( SELECT {$this->db->dbprefix('sale_items')}.sale_id,{$this->db->dbprefix('sale_items')}.subtotal, {$this->db->dbprefix('sale_items')}.product_id, {$this->db->dbprefix('sale_items')}.serial_no, GROUP_CONCAT(CONCAT('',{$this->db->dbprefix('sale_items')}.product_name, IF({$this->db->dbprefix('product_variants')}.name <> 'NULL',CONCAT(' (',{$this->db->dbprefix('product_variants')}.name,')'),''), '__', {$this->db->dbprefix('sale_items')}.quantity) SEPARATOR '___') as item_nane from {$this->db->dbprefix('sale_items')} ";
            $si .= "LEFT JOIN {$this->db->dbprefix('product_variants')} ON {$this->db->dbprefix('sale_items')}.option_id = {$this->db->dbprefix('product_variants')}.id";
            if ($product) {
                $si .= " WHERE {$this->db->dbprefix('sale_items')}.product_id = {$product} ";
            }

            $si .= " GROUP BY {$this->db->dbprefix('sale_items')}.sale_id ) FSI";
            $this->load->library('datatables');
//REPLACE(reference_no, SUBSTRING_INDEX(reference_no, '/', -1), {$this->db->dbprefix('sales')}.id) as reference_no
//                       $this->datatables->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference_no, invoice_no,biller, customer, FSI.item_nane as iname, (grand_total+rounding), paid, (grand_total+rounding -paid) as balance, payment_status, {$this->db->dbprefix('sales')}.id as id", FALSE)->from('sales')->join($si, 'FSI.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');
            $this->datatables->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference_no, invoice_no,biller, customer, sma_warehouses.name ,FSI.item_nane as iname, FSI.subtotal, paid, (grand_total+rounding -paid) as balance, payment_status, {$this->db->dbprefix('sales')}.id as id", FALSE)->from('sales')->join($si, 'FSI.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');


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
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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
                $this->db->where('sales.created_by', $this->input->get('user'));
            }
        } else {
            if ($this->session->userdata('view_right') == '0') {
                if ($user) {
                    $this->db->where('sales.created_by', $user);
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

            $this->db->select("date, reference_no, biller, customer, GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('quote_items') . ".product_name, ' (', " . $this->db->dbprefix('quote_items') . ".quantity, ')') SEPARATOR ' ') as iname, grand_total, status", FALSE)->from('quotes')->join('quote_items', 'quote_items.quote_id=quotes.id', 'left')->join('warehouses', 'warehouses.id=quotes.warehouse_id', 'left')->group_by('quotes.id');

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
                $this->db->where('DATE(' . $this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('product'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Qty'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('balance'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('status'));

                $row = 3;
                $total = 0;
                $paid = 0;
                $balance = 0;
                foreach ($data as $data_row) {


                    $in = $data_row->iname;
                    preg_match_all('/\(([0-9. ]+?)\)/', $in, $out);
                    $qty = implode(", ", $out[1]);

                    $product_name = preg_replace("/\([^)]+\)/", "", $in);


                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->wname);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->supplier);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $product_name);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $qty);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->paid);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, ($data_row->grand_total - $data_row->paid));
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row->status);
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

            $pi = "( SELECT purchase_id, product_id, subtotal, (GROUP_CONCAT(CONCAT({$this->db->dbprefix('purchase_items')}.product_name, '__', {$this->db->dbprefix('purchase_items')}.quantity) SEPARATOR '___')) as item_nane from {$this->db->dbprefix('purchase_items')} ";
            if ($product) {
                $pi .= " WHERE {$this->db->dbprefix('purchase_items')}.product_id = {$product} ";
            }
            $pi .= " GROUP BY {$this->db->dbprefix('purchase_items')}.purchase_id ) FPI";

            $this->load->library('datatables');
            //$this->datatables->select("DATE_FORMAT({$this->db->dbprefix('purchases')}.date, '%Y-%m-%d %T') as date, reference_no, {$this->db->dbprefix('warehouses')}.name as wname, supplier, (FPI.item_nane) as iname, (grand_total+rounding) as grand_total, paid, (grand_total+rounding-paid) as balance, {$this->db->dbprefix('purchases')}.status, {$this->db->dbprefix('purchases')}.id as id", FALSE)->from('purchases')->join($pi, 'FPI.purchase_id=purchases.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left');
            // ->group_by('purchases.id');
            $this->datatables->select("DATE_FORMAT({$this->db->dbprefix('purchases')}.date, '%Y-%m-%d %T') as date, reference_no, {$this->db->dbprefix('warehouses')}.name as wname, supplier, (FPI.item_nane) as iname, (grand_total+rounding) as grand_total, paid, (grand_total+rounding-paid) as balance, {$this->db->dbprefix('purchases')}.status, {$this->db->dbprefix('purchases')}.id as id", FALSE)->from('purchases')->join($pi, 'FPI.purchase_id=purchases.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left');


            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->datatables->where('purchases.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('purchases.created_by', $user);
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
                $this->datatables->where('DATE(' . $this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function getPurchasesReportProducts($pdf = NULL, $xls = NULL) {
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
                $this->db->where('DATE(' . $this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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

            $pi = "( SELECT purchase_id, product_id, subtotal, (GROUP_CONCAT(CONCAT({$this->db->dbprefix('purchase_items')}.product_name, '__', {$this->db->dbprefix('purchase_items')}.quantity) SEPARATOR '___')) as item_nane from {$this->db->dbprefix('purchase_items')} ";
            if ($product) {
                $pi .= " WHERE {$this->db->dbprefix('purchase_items')}.product_id = {$product} ";
            }
            $pi .= " GROUP BY {$this->db->dbprefix('purchase_items')}.purchase_id ) FPI";

            $this->load->library('datatables');
            $this->datatables->select("DATE_FORMAT({$this->db->dbprefix('purchases')}.date, '%Y-%m-%d %T') as date, reference_no, {$this->db->dbprefix('warehouses')}.name as wname, supplier, (FPI.item_nane) as iname, (FPI.subtotal) as grand_total, paid, (grand_total+rounding-paid) as balance, {$this->db->dbprefix('purchases')}.status, {$this->db->dbprefix('purchases')}.id as id", FALSE)->from('purchases')->join($pi, 'FPI.purchase_id=purchases.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left');
            // ->group_by('purchases.id');

            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->datatables->where('purchases.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('purchases.created_by', $user);
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
                $this->datatables->where('DATE(' . $this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function payments() {
        $this->sma->checkPermissions('payments');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['customer'] = $this->reports_model->getCustomerCompanies();
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

        if ($start_date) {
            $start_date = date('Y-m-d', strtotime($start_date));
            $end_date = date('Y-m-d', strtotime($end_date));
        }
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
            $this->datatables->select("DATE_FORMAT({$this->db->dbprefix('payments')}.date, '%d/%m/%Y %T') as date, " . $this->db->dbprefix('payments') . ".reference_no as payment_ref, " . $this->db->dbprefix('sales') . ".reference_no as sale_ref, " . $this->db->dbprefix('sales') . ".invoice_no as invoice_no, " . $this->db->dbprefix('purchases') . ".reference_no as purchase_ref, " . $this->db->dbprefix('orders') . ".reference_no as order_ref, paid_by, amount, type, {$this->db->dbprefix('payments')}.id as id")
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
                $this->datatables->where('DATE(' . $this->db->dbprefix('payments') . '.date) >= "' . $start_date . '"');
                $this->datatables->where('DATE(' . $this->db->dbprefix('payments') . '.date) <= "' . $end_date . '"');
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

            $this->datatables->select(' DATE_FORMAT(sma_payments.date, "%d/%m/%Y") as date, sum(sma_payments.amount) as Total, sma_payments.type');

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

            $this->datatables->group_by('DATE_FORMAT(sma_payments.date, "%d/%m/%Y"),sma_payments.type');

            $this->db->order_by('sma_payments.date', 'DESC');
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

        $datevalue = $this->sma->fld($datevalue);
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
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if ($pdf || $xls || $img) {

            $this->db->select($this->db->dbprefix('companies') . ".id as id, company, name, phone, email, count(" . $this->db->dbprefix('sales') . ".id) as total, (COALESCE(sum(grand_total), 0)+COALESCE(sum(rounding), 0)) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) + COALESCE(sum(rounding), 0)- COALESCE(sum(paid), 0)) as balance, sma_companies.deposit_amount", FALSE)->from("companies")->join('sales', 'sales.customer_id=companies.id')->where('companies.group_name', 'customer')->order_by('companies.company asc')->group_by('companies.id');
            if ($start_date) {
                //$this->db->where($this->db->dbprefix('sales') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) >= "' . $start_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) <= "' . $end_date . '"');
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

                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Recharge Amount'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Used Amount'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('Deposit Balance

'));

                $row = 3;
                foreach ($data as $data_row) {
                    $getDeposit = $this->reports_model->getDepositReEx($data_row->id, $start_date, $end_date);

                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->company);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->phone);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->email);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->total);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $this->sma->formatMoney($data_row->total_amount));
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $this->sma->formatMoney($data_row->paid));
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $this->sma->formatMoney($data_row->balance));

                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $this->sma->formatMoney($getDeposit['recharge_amount']));
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $this->sma->formatMoney($getDeposit['used_amount']));
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $this->sma->formatMoney($data_row->deposit_amount));



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
            if ($start_date) {
                $s .= " WHERE {$this->db->dbprefix('sales')}.date BETWEEN  '$start_date' and  '$end_date' ";
            }
            $s .= "GROUP BY {$this->db->dbprefix('sales')}.customer_id ) FS";


            $this->load->library('datatables');

            /* $this->datatables->select($this->db->dbprefix('companies') . ".id as company_id, company, name, phone, email, FS.total, FS.total_amount, FS.paid, FS.balance, sma_companies.deposit_amount", FALSE)->from("companies")
              ->join($s, 'FS.customer_id=companies.id')
              ->join('deposits','deposits.company_id = companies.id','');

              $this->datatables->where('companies.group_name', 'customer')->group_by('companies.id')
              ->add_column("Recharge Amount",'')
              ->add_column("Used Amount",'')
              ->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/customer_report/$1') . "/" . trim($start_date) . "/" . $end_date . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")->unset_column('id'); */

            $this->datatables->select("company, name, phone, email, FS.total, FS.total_amount, FS.paid, FS.balance,NULL, NULL , sma_companies.deposit_amount,sma_companies.id ", FALSE)->from("companies")
                    ->join($s, 'FS.customer_id=companies.id')
                    ->join('deposits', 'deposits.company_id = companies.id', '');


            $this->datatables->where('companies.group_name', 'customer')->group_by('companies.id')
                    ->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/customer_report/$1') . "/" . trim($start_date) . "/" . $end_date . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")->unset_column('id');


            echo $this->datatables->generate();
        }
    }

    function customer_report($user_id = NULL, $start_date = Null, $end_date = Null) {
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
        if ($start_date != Null) {
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
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }

        if ($pdf || $xls || $img) {

            $this->db->select($this->db->dbprefix('companies') . ".id as id, company, name, phone, email, count({$this->db->dbprefix('purchases')}.id) as total, COALESCE(sum(grand_total), 0) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) - COALESCE(sum(paid), 0)) as balance", FALSE)->from("companies")->join('purchases', 'purchases.supplier_id=companies.id')->where('companies.group_name', 'supplier')->order_by('companies.company asc')->group_by('companies.id');
            if ($start_date) {
                $this->db->where('DATE(' . $this->db->dbprefix('purchases') . '.date) >= "' . $start_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('purchases') . '.date) <= "' . $end_date . '"');
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
            if ($start_date) {
                $p .= " WHERE {$this->db->dbprefix('purchases')}.date BETWEEN  '$start_date' and  '$end_date' ";
            }
            $p .= "GROUP BY {$this->db->dbprefix('purchases')}.supplier_id ) FP";

            $this->load->library('datatables');
            $this->datatables->select($this->db->dbprefix('companies') . ".id as id, company, name, phone, email, FP.total, FP.total_amount, FP.paid, FP.balance", FALSE)->from("companies")->join($p, 'FP.supplier_id=companies.id')->where('companies.group_name', 'supplier')->group_by('companies.id')->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/supplier_report/$1') . "/" . trim($start_date) . "/" . $end_date . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")->unset_column('id');
            echo $this->datatables->generate();
        }
    }

    function supplier_report($user_id = NULL, $start_date = NULL, $end_date = NULL) {
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
        if ($start_date != Null) {
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
                $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/' . $filename . '.pdf', __FILE__));
                redirect("reports/create_image/" . $filename . ".pdf");
                exit();
            }
        }
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse_id'] = $this->session->userdata('warehouse_id');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('daily_purchases_report')));
        $meta = array('page_title' => lang('daily_purchases_report'), 'bc' => $bc);
        $this->page_construct('reports/daily_purchases', $meta, $this->data);
    }

    function monthly_purchases($warehouse_id = NULL, $year = NULL, $pdf = NULL, $user_id = NULL) {
        $this->sma->checkPermissions();
        if ($warehouse_id != NULL) {
            $warehouse_id = $warehouse_id;
        } elseif (!$this->Owner && !$this->Admin && $this->session->userdata('warehouse_id')) {
            $warehouse_id = $warehouse_id = str_replace(",", "_", $this->session->userdata('warehouse_id'));
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
        $this->data['purchases'] = $user_id ? $this->reports_model->getStaffMonthlyPurchases($user_id, $year, $warehouse_id) : $this->reports_model->getMonthlyPurchases($year, $warehouse_id);
        $_purchases = $this->data['purchases'];
        if ($pdf) {
            $purchases_pdf = array();
            foreach ($_purchases as $data_row) {
                $purchases_pdf[$data_row->date] = $data_row;
            }
            sort($purchases_pdf);

            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'DDDDDD'))));

            $this->excel->getActiveSheet()->getStyle("A1:G1")->applyFromArray($style);

            $this->excel->getActiveSheet()->mergeCells('A1:G1');
            $this->excel->getActiveSheet()->SetCellValue('A1', lang('Monthly Purchase Report ') . date("Y", mktime(0, 0, 0, 1, 1, $year)));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('Sr.No'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Date'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Discount'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Shipping'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Product Tax'));
            $this->excel->getActiveSheet()->SetCellValue('F2', lang('Order Tax'));
            $this->excel->getActiveSheet()->SetCellValue('G2', lang('Total'));
            $row = 3;

            $sr = 1;
            foreach ($purchases_pdf as $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sr);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, date("M-Y", mktime(0, 0, 0, $data_row->date, 1, $year)));
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
            $filename = 'monthly_purchases_report';

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
                $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/monthly_purchases_report.pdf', __FILE__));
                redirect("reports/create_image/monthly_purchases_report.pdf");
                exit();
            }
        }

        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse_id'] = $this->session->userdata('warehouse_id');
        $this->data['sel_warehouse'] = $warehouse_id ? (strpos($warehouse_id, '_') !== false) ? NULL : $this->site->getWarehouseByID($warehouse_id) : NULL;
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('monthly_purchases_report')));
        $meta = array('page_title' => lang('monthly_purchases_report'), 'bc' => $bc);
        $this->page_construct('reports/monthly_purchases', $meta, $this->data);
    }

    function adjustments($warehouse_id = NULL) {
        $this->sma->checkPermissions('products');

        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('adjustments_report')));
        $meta = array('page_title' => lang('adjustments_report'), 'bc' => $bc);
        $this->page_construct('reports/adjustments', $meta, $this->data);
    }

    function getAdjustmentReport($pdf = NULL, $xls = NULL, $img = NULL) {

        $this->sma->checkPermissions('products', TRUE);

        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $serial = $this->input->get('serial') ? $this->input->get('serial') : NULL;

        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && $user == NULL && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        if ($pdf || $xls || $img) {

            //  $ai = "( SELECT adjustment_id, product_id, serial_no, GROUP_CONCAT(CONCAT({$this->db->dbprefix('products')}.name, ' (', (CASE WHEN {$this->db->dbprefix('adjustment_items')}.type  = 'subtraction' THEN (0-{$this->db->dbprefix('adjustment_items')}.quantity) ELSE {$this->db->dbprefix('adjustment_items')}.quantity END), ')') SEPARATOR '\n') as item_nane from {$this->db->dbprefix('adjustment_items')} LEFT JOIN {$this->db->dbprefix('products')} ON {$this->db->dbprefix('products')}.id={$this->db->dbprefix('adjustment_items')}.product_id GROUP BY {$this->db->dbprefix('adjustment_items')}.adjustment_id ) FAI";
            // $this->db->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference_no, warehouses.name as wh_name, CONCAT({$this->db->dbprefix('users')}.first_name, ' ', {$this->db->dbprefix('users')}.last_name) as created_by, note, FAI.item_nane as iname, {$this->db->dbprefix('adjustments')}.id as id", FALSE)->from('adjustments')->join($ai, 'FAI.adjustment_id=adjustments.id', 'left')->join('users', 'users.id=adjustments.created_by', 'left')->join('warehouses', 'warehouses.id=adjustments.warehouse_id', 'left');

            $this->db->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference_no, warehouses.name as wh_name, CONCAT({$this->db->dbprefix('users')}.first_name, ' ', {$this->db->dbprefix('users')}.last_name) as created_by, note, {$this->db->dbprefix('adjustments')}.id as id ", FALSE)
                    ->from('adjustments')
                    ->join('users', 'users.id=adjustments.created_by', 'left')
                    ->join('warehouses', 'warehouses.id=adjustments.warehouse_id', 'left');


            /* if($user)
              {
              $this->db->where('adjustments.created_by', $user);
              } */
            if ($product) {
                $this->db->where('FAI.product_id', $product, FALSE);
            }
            if ($serial) {
                $this->db->like('FAI.serial_no', $serial, FALSE);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('adjustments.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($user) {
                $this->db->where('adjustments.created_by', $user);
            }
            if ($reference_no) {
                $this->db->like('adjustments.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->db->where('DATE(' . $this->db->dbprefix('adjustments') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Adjustments Report');

                $this->excel->getActiveSheet()->setTitle(lang('adjustments_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('warehouse'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('created_by'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('note'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('products'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Qty'));

                $row = 3;
                foreach ($data as $data_row) {

                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->wh_name);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->created_by);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $this->sma->decode_html($data_row->note));
                    // $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->iname);

                    $ProductName = '';
                    $productQTY = '';
                    $sql_product = "SELECT s_ad.adjustment_id as id, s_ad.product_id,s_ad.option_id, s_pro.name as productname ,(CASE WHEN s_ad.type = 'subtraction' THEN (0-s_ad.quantity) ELSE s_ad.quantity END) as quantity   from `sma_adjustment_items` s_ad  LEFT JOIN sma_products as s_pro ON s_pro.id = s_ad.product_id  WHERE s_ad.adjustment_id = '" . $data_row->id . "'   "; // $sql_product = "SELECT s_ad.id, s_pro.name as productname ,(CASE WHEN s_ad.type = 'subtraction' THEN (0-s_ad.quantity) ELSE s_ad.quantity END) as quantity   from `sma_adjustment_items` s_ad  LEFT JOIN sma_products as s_pro ON s_pro.id = s_ad.product_id  WHERE s_ad.adjustment_id = '".$data_row->id."'   ";//

                    $Res_product = $this->db->query($sql_product);
                    $Row_product = $Res_product->result_array();
                    foreach ($Row_product as $Res_row) {
                        //$Sql = "SELECT GROUP_CONCAT(CONCAT(spv.name) SEPARATOR '___')  as varient_name FROM `sma_adjustment_items` sai inner join sma_product_variants spv on sai.`option_id` = spv.id WHERE sai.adjustment_id='".$Res_row['id']."' and spv.product_id='".$Res_row['product_id']."'";

                        $Sql = "SELECT GROUP_CONCAT(CONCAT(spv.name) SEPARATOR '___')  as varient_name FROM `sma_adjustment_items` sai inner join sma_product_variants spv on sai.`option_id` = spv.id WHERE sai.adjustment_id='" . $Res_row['id'] . "' and spv.product_id='" . $Res_row['product_id'] . "' and  sai.`option_id` =  '" . $Res_row['option_id'] . "' "; //sai.`option_id` = spv.id
                        $Res = $this->db->query($Sql);
                        $Row = $Res->row();
                        $variant = isset($Row->varient_name) ? '_' . $Row->varient_name : '';
                        //$ProductName .= $Res_row['productname'] . $variant . '(' . $Res_row['quantity'] . ') ' . "\r\n";
                        $ProductName = $Res_row['productname'] . $variant; // . "\r\n";
                        $productQTY = $Res_row['quantity']; // . ', ' . "\r\n";

                        $this->excel->getActiveSheet()->SetCellValue('F' . $row, $ProductName);
                        $this->excel->getActiveSheet()->SetCellValue('G' . $row, $productQTY);

                        $row++;
                    }
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(40);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
                $filename = 'adjustments_report';
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
                    $this->excel->getActiveSheet()->getStyle('F2:F' . $row)->getAlignment()->setWrapText(TRUE);
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/adjustments_report.pdf', __FILE__));
                    redirect("reports/create_image/adjustments_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));

            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $ai = "( SELECT adjustment_id, product_id, serial_no, GROUP_CONCAT(CONCAT({$this->db->dbprefix('products')}.name, '__', (CASE WHEN {$this->db->dbprefix('adjustment_items')}.type  = 'subtraction' THEN (0-{$this->db->dbprefix('adjustment_items')}.quantity) ELSE {$this->db->dbprefix('adjustment_items')}.quantity END)) SEPARATOR '___') as item_nane from {$this->db->dbprefix('adjustment_items')} LEFT JOIN {$this->db->dbprefix('products')} ON {$this->db->dbprefix('products')}.id={$this->db->dbprefix('adjustment_items')}.product_id ";
            if ($product) {
                $ai .= " WHERE {$this->db->dbprefix('adjustment_items')}.product_id = {$product} ";
            }
            $ai .= " GROUP BY {$this->db->dbprefix('adjustment_items')}.adjustment_id ) FAI";
            $this->load->library('datatables');
            $this->datatables->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference_no, warehouses.name as wh_name, CONCAT({$this->db->dbprefix('users')}.first_name, ' ', {$this->db->dbprefix('users')}.last_name) as created_by, note, FAI.item_nane as iname, {$this->db->dbprefix('adjustments')}.id as id", FALSE)->from('adjustments')->join($ai, 'FAI.adjustment_id=adjustments.id', 'left')->join('users', 'users.id=adjustments.created_by', 'left')->join('warehouses', 'warehouses.id=adjustments.warehouse_id', 'left');

            /* if($user)
              {
              $this->datatables->where('adjustments.created_by', $user);
              } */
            if ($product) {
                $this->datatables->where('FAI.product_id', $product, FALSE);
            }
            if ($serial) {
                $this->datatables->like('FAI.serial_no', $serial, FALSE);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('adjustments.warehouse_id IN(' . $getwarehouse . ')');
            }
            if ($user) {
                $this->datatables->where('adjustments.created_by', $user);
            }
            if ($reference_no) {
                $this->datatables->like('adjustments.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->datatables->where('DATE(' . $this->db->dbprefix('adjustments') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function get_deposits($company_id = NULL) {
        $this->sma->checkPermissions('customers', TRUE);
        $this->load->library('datatables');
        $this->datatables->select("date, amount, paid_by, CONCAT({$this->db->dbprefix('users')}.first_name, ' ', {$this->db->dbprefix('users')}.last_name) as created_by, note", FALSE)->from("deposits")->join('users', 'users.id=deposits.created_by', 'left')->where($this->db->dbprefix('deposits') . '.company_id', $company_id);
        echo $this->datatables->generate();
    }

    public function sales_gst_report() {
        $this->sma->checkPermissions('sales');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['salegstcount'] = $this->getCountSalesGst();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);

        $this->page_construct('reports/sales_custome_report', $meta, $this->data);
    }

    public function getSalesReportC($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('sales', TRUE);
        $SalesIds = '';
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $biller = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $serial = $this->input->get('serial') ? $this->input->get('serial') : NULL;
        $gstn_opt = $this->input->get('gstn_opt') ? $this->input->get('gstn_opt') : NULL;
        $gstn_no = $this->input->get('gstn_no') ? $this->input->get('gstn_no') : NULL;
        $hsn_code = $this->input->get('hsn_code') ? $this->input->get('hsn_code') : NULL;
        $max_export_sales = $this->input->get('max_export_sales') ? $this->input->get('max_export_sales') : '0-200'; //0-500
        if (!empty($hsn_code)) {
            $SalesIds = $this->reports_model->getSaleIdByHsn($hsn_code);
        }
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        if ($pdf || $xls || $img) {

            list($start, $limit) = explode('-', $max_export_sales);

            $this->db->select("sales.id as sale_id,sales.date, sales.reference_no, sales.invoice_no,sales.biller, sales.customer,sales.product_tax as total_tax , 
                IF(comp.gstn_no IS NULL or comp.gstn_no = '', '-', comp.gstn_no) as gstn_no,   comp.address,  comp.city,  comp.phone,  comp.email , 
                sma_sales.grand_total as grand_total, sma_sales.paid as paid,sma_sales.rounding as rounding,sma_payments.paid_by, sales.payment_status", FALSE)
                    ->from('sales')
                    ->join('companies comp', 'sales.customer_id=comp.id', 'left')
                    ->join('sma_payments ', 'sales.id=sma_payments.sale_id', 'left')
                    ->limit($limit, $start)
                    ->group_by('sales.id')
                    ->order_by('sales.date desc');
            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->db->where('sales.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('sales.created_by', $user);
                    }
                }
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

            if ($gstn_opt) {
                switch ($gstn_opt) {
                    case '-1':
                        $this->db->where("comp.gstn_no IS NULL OR comp.gstn_no = '' ");
                        break;
                    case '1':
                        $this->db->where("comp.gstn_no IS NOT NULL and comp.gstn_no != '' ");
                        break;
                    default:
                        break;
                }
            }

            if ($gstn_no) {
                $this->db->where("comp.gstn_no = '" . $gstn_no . "' ");
            }
            if ($SalesIds) {
                $this->db->where('sales.id in (' . $SalesIds . ')');
            }

            $q = $this->db->get();
            $data_sales = [];
            $saleCount = 0;
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    if (!in_array($row->sale_id, $data_sales)) {
                        $data_sales[] = $row->sale_id;
                    }

                    //Sales Details
                    $data[$row->sale_id]['sale_id'] = $row->sale_id;
                    $data[$row->sale_id]['date'] = $row->date;
                    $data[$row->sale_id]['reference_no'] = $row->reference_no;
                    $data[$row->sale_id]['invoice_no'] = $row->invoice_no;
                    $data[$row->sale_id]['biller'] = $row->biller;
                    $data[$row->sale_id]['customer'] = $row->customer;
                    $cantact = ($row->address) ? $row->address : '';
                    $cantact .= ($row->city) ? ' City:' . $row->city : '';
                    $cantact .= ($row->phone) ? ' Phone:' . $row->phone : '';
                    $cantact .= ($row->email) ? ' Email:' . $row->email : '';
                    $data[$row->sale_id]['address'] = $cantact;
                    $data[$row->sale_id]['gstn_no'] = $row->gstn_no;
                    $data[$row->sale_id]['grand_total'] = $row->grand_total + $row->rounding;
                    $data[$row->sale_id]['taxable_amt'] = $row->grand_total - $row->total_tax;
                    $data[$row->sale_id]['total_tax'] = $row->total_tax;
                    $data[$row->sale_id]['paid'] = $row->paid;
                    $data[$row->sale_id]['balance'] = $row->grand_total + $row->rounding - $row->paid;
                    $data[$row->sale_id]['paid_by'] = $row->paid_by;
                    $data[$row->sale_id]['payment_status'] = $row->payment_status;
                }//endforeach

                $uniqueSalesIds = array_unique($data_sales);

                //Get Sale items details
                $SalesItems = $this->reports_model->getSalesItemsBySaleIds($uniqueSalesIds);
                if (is_array($SalesItems)) {
                    foreach ($SalesItems as $key => $SaleItemsRow) {
                        //Sales Items Details
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['items_id'] = $SaleItemsRow->items_id;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['code'] = $SaleItemsRow->product_code;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['name'] = $SaleItemsRow->product_name;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['variantname'] = $SaleItemsRow->variant_name;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['gst'] = ($SaleItemsRow->gst) ? substr($SaleItemsRow->gst, 0, -3) : 0;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['hsn_code'] = $SaleItemsRow->hsn_code;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['quantity'] = $SaleItemsRow->quantity;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['unit'] = $SaleItemsRow->unit;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['tax_amt'] = ($SaleItemsRow->item_tax) ? $SaleItemsRow->item_tax : 0;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['subtotal'] = $SaleItemsRow->subtotal;
                    }//end foreach
                }//end if
                //Get Sales Items GST Attributes details  
                $SalestaxAttrib = $this->reports_model->getSalesTaxAttrBySalesIds($uniqueSalesIds);

                if (is_array($SalestaxAttrib)) {
                    foreach ($SalestaxAttrib as $key => $taxItemRow) {

                        switch ($taxItemRow->attr_code) {
                            case 'CGST':
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['cgst_per'] = ($taxItemRow->attr_per) ? $taxItemRow->attr_per : 0;
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['CGST'] = ($taxItemRow->tax_amount) ? $taxItemRow->tax_amount : 0;
                                break;
                            case 'SGST':
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['sgst_per'] = ($taxItemRow->attr_per) ? $taxItemRow->attr_per : 0;
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['SGST'] = ($taxItemRow->tax_amount) ? $taxItemRow->tax_amount : 0;
                                break;
                            case 'IGST':
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['igst_per'] = ($taxItemRow->attr_per) ? $taxItemRow->attr_per : 0;
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['IGST'] = ($taxItemRow->tax_amount) ? $taxItemRow->tax_amount : 0;
                                break;

                            case 'VAT':
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['vat'] = ($taxItemRow->attr_per) ? $taxItemRow->attr_per : 0;
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['VAT'] = ($taxItemRow->tax_amount) ? $taxItemRow->tax_amount : 0;
                                break;
                            case 'CESS':
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['Cess'] = ($taxItemRow->attr_per) ? $taxItemRow->attr_per : 0;
                                $data[$taxItemRow->sale_id]['items'][$taxItemRow->item_id]['CESS'] = ($taxItemRow->tax_amount) ? $taxItemRow->tax_amount : 0;
                                break;
                        }//end switch.
                    }//end foreach.
                }//end if.
            } else {
                $data = NULL;
            }

            if (!empty($data)) {
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:AG1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:AG1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'GST Sales Report');

                $this->excel->getActiveSheet()->setTitle(lang('sales_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('sr no'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Invoice No'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('biller'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('customer'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('customer') . ' Contacts');
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('gstn'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Grand Total (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Taxable Amount (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('Tax Amount (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('Paid (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('M2', lang('Balance (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('N2', lang('Payment Method'));
                $this->excel->getActiveSheet()->SetCellValue('O2', lang('Payment Status'));

                //Sales Items Detail
                $this->excel->getActiveSheet()->SetCellValue('P2', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('Q2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('R2', lang('Varient'));
                $this->excel->getActiveSheet()->SetCellValue('S2', lang('hsn_code'));
                $this->excel->getActiveSheet()->SetCellValue('T2', lang('GST Rate (%)'));
                $this->excel->getActiveSheet()->SetCellValue('U2', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('V2', lang('unit'));
                $this->excel->getActiveSheet()->SetCellValue('W2', lang('CGST (%)'));
                $this->excel->getActiveSheet()->SetCellValue('X2', lang('CGST (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('Y2', lang('SGST (%)'));
                $this->excel->getActiveSheet()->SetCellValue('Z2', lang('SGST (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('AA2', lang('IGST (%)'));
                $this->excel->getActiveSheet()->SetCellValue('AB2', lang('IGST (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('AC2', lang('VAT (%)'));
                $this->excel->getActiveSheet()->SetCellValue('AD2', lang('VAT (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('AE2', lang('CESS (%)'));
                $this->excel->getActiveSheet()->SetCellValue('AF2', lang('CESS (Rs)'));
                // $this->excel->getActiveSheet()->SetCellValue('Z1', lang('Item Tax (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('AG2', lang('Subtotal (Rs)'));

                $row = 3;
                $cgst = 0;
                $sgst = 0;
                $igst = 0;
                $vat = 0;
                $cess = 0;
                $total = 0;
                $paid = 0;
                $balance = 0;
                $total_taxable_amt = 0;
                $totalSubtotal = 0;
                $sr = ($start) ? ($start - 1) : 0;

                $this->excel->getActiveSheet()->getStyle("A" . $row . ":Z" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                foreach ($data as $sale_id => $salesdata) {
                    $sale_data = (object) $salesdata;

                    $sr++;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, ($sr));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $this->sma->hrld($sale_data->date));
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $sale_data->invoice_no);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $sale_data->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $sale_data->biller);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $sale_data->customer);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $sale_data->address);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $sale_data->gstn_no);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $sale_data->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $sale_data->taxable_amt);
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $sale_data->total_tax);
                    $this->excel->getActiveSheet()->SetCellValue('L' . $row, $sale_data->paid);
                    $this->excel->getActiveSheet()->SetCellValue('M' . $row, $sale_data->balance);
                    $this->excel->getActiveSheet()->SetCellValue('N' . $row, $this->reports_model->getpaymentmode($sale_data->sale_id));
                    $this->excel->getActiveSheet()->SetCellValue('O' . $row, $sale_data->payment_status);

                    if (!empty($sale_data->items)) {
                        foreach ($sale_data->items as $saleitem_id => $salesItemsData) {

                            $sales_items_data = (object) $salesItemsData;

                            $this->excel->getActiveSheet()->SetCellValue('P' . $row, $sales_items_data->code);
                            $this->excel->getActiveSheet()->SetCellValue('Q' . $row, $sales_items_data->name);
                            $this->excel->getActiveSheet()->SetCellValue('R' . $row, $sales_items_data->variantname);
                            $this->excel->getActiveSheet()->SetCellValue('S' . $row, $sales_items_data->hsn_code);
                            $this->excel->getActiveSheet()->SetCellValue('T' . $row, $sales_items_data->gst);
                            $this->excel->getActiveSheet()->SetCellValue('U' . $row, $sales_items_data->quantity);
                            $this->excel->getActiveSheet()->SetCellValue('V' . $row, lang($sales_items_data->unit));
                            $this->excel->getActiveSheet()->SetCellValue('W' . $row, $sales_items_data->cgst_per);
                            $this->excel->getActiveSheet()->SetCellValue('X' . $row, $sales_items_data->CGST);
                            $this->excel->getActiveSheet()->SetCellValue('Y' . $row, $sales_items_data->sgst_per);
                            $this->excel->getActiveSheet()->SetCellValue('Z' . $row, $sales_items_data->SGST);
                            $this->excel->getActiveSheet()->SetCellValue('AA' . $row, $sales_items_data->igst_per);
                            $this->excel->getActiveSheet()->SetCellValue('AB' . $row, $sales_items_data->IGST);
                            $this->excel->getActiveSheet()->SetCellValue('AC' . $row, $sales_items_data->vat);
                            $this->excel->getActiveSheet()->SetCellValue('AD' . $row, $sales_items_data->VAT);
                            $this->excel->getActiveSheet()->SetCellValue('AE' . $row, $sales_items_data->Cess);
                            $this->excel->getActiveSheet()->SetCellValue('AF' . $row, $sales_items_data->CESS);
//                            $this->excel->getActiveSheet()->SetCellValue('Z' . $row, $sales_items_data->igst_per);
//                            $this->excel->getActiveSheet()->SetCellValue('a' . $row, $sales_items_data->IGST);

                            $this->excel->getActiveSheet()->SetCellValue('AG' . $row, $sales_items_data->subtotal);

                            $cgst += $sales_items_data->CGST;
                            $sgst += $sales_items_data->SGST;
                            $igst += $sales_items_data->IGST;

                            $vat += $sales_items_data->VAT;
                            $cess += $sales_items_data->CESS;
                            $totalSubtotal += $sales_items_data->subtotal;
                            $row++;
                        }//end foreach
                    }//end if.
                    $this->excel->getActiveSheet()->getStyle("A" . $row . ":AF" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

                    $total += $sale_data->grand_total;
                    $paid += $sale_data->paid;
                    $total_tax += $sale_data->total_tax;
                    $balance += $sale_data->balance;
                    $total_taxable_amt += $sale_data->taxable_amt;
                }//end outer foreach

                $this->excel->getActiveSheet()->getStyle("A" . $row . ":AF" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->getStyle("A" . $row . ":AF" . $row)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                $this->excel->getActiveSheet()->SetCellValue('H' . $row, 'Total Calculated Value:');

                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $total);
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $total_taxable_amt);
                $this->excel->getActiveSheet()->SetCellValue('K' . $row, $total_tax);
                $this->excel->getActiveSheet()->SetCellValue('L' . $row, $paid);
                $this->excel->getActiveSheet()->SetCellValue('M' . $row, $balance);

                $this->excel->getActiveSheet()->SetCellValue('W' . $row, 'Total CGST:');
                $this->excel->getActiveSheet()->SetCellValue('X' . $row, $cgst);
                $this->excel->getActiveSheet()->SetCellValue('Y' . $row, 'Total SGST:');
                $this->excel->getActiveSheet()->SetCellValue('Z' . $row, $sgst);
                $this->excel->getActiveSheet()->SetCellValue('AA' . $row, 'Total IGST:');
                $this->excel->getActiveSheet()->SetCellValue('AB' . $row, $igst);
                $this->excel->getActiveSheet()->SetCellValue('AC' . $row, 'Total VAT:');
                $this->excel->getActiveSheet()->SetCellValue('AD' . $row, $vat);
                $this->excel->getActiveSheet()->SetCellValue('AE' . $row, 'Total CESS:');
                $this->excel->getActiveSheet()->SetCellValue('AF' . $row, $cess);
                $this->excel->getActiveSheet()->SetCellValue('AG' . $row, $totalSubtotal);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(40);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('O')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('P')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('Q')->setWidth(12);
                $this->excel->getActiveSheet()->getColumnDimension('R')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('S')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('T')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('U')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('V')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('W')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('X')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('Y')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('Z')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('AA')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('AB')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('AC')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('AD')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('AE')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('AF')->setWidth(15);

                $filename = 'sales_gst_report_' . $max_export_sales . '_' . time();
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getDefaultStyle()->getAlignment()->applyFromArray(
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
                    $this->excel->getActiveSheet()->getStyle('F2:F' . ($row + 1))->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if (img) {
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/sales_gst_report.pdf', __FILE__));
                    redirect("reports/create_image/sales_gst_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            //REPLACE(sma_sales.reference_no, SUBSTRING_INDEX(sma_sales.reference_no, '/', -1),{$this->db->dbprefix('sales')}.id)
            $this->datatables->select("
            DATE_FORMAT(sma_sales.date, '%Y-%m-%d %T') as date,sma_sales.invoice_no,
            sma_sales.reference_no as reference_no,            
            biller,
            customer,
            state,
            IF(comp.gstn_no IS NULL or comp.gstn_no = '', '-', comp.gstn_no) as gstn_no,
            (SELECT (GROUP_CONCAT(DISTINCT hsn_code)) as hsn FROM `sma_sale_items` WHERE sma_sale_items.sale_id = `sma_sales`.`id`) as hsn,
            (SELECT format( sum(sma_sale_items.quantity),2)  as qty FROM `sma_sale_items` WHERE  sma_sale_items.sale_id = `sma_sales`.`id`) as qty,
            (SELECT (GROUP_CONCAT(DISTINCT CONCAT( ' ' ,product_unit_code))) as units FROM `sma_sale_items` WHERE  sma_sale_items.sale_id = `sma_sales`.`id`) as units,

            (SELECT GROUP_CONCAT( CONCAT( ' (' ,`attr_per`, '%)Rs.', format(`tax_amount`,2) ) ) AS CGST 
            FROM `view_sales_gst_report`  
            WHERE `view_sales_gst_report`.`sale_id` =  `sma_sales`.`id` 
            AND `attr_code` =  'CGST' 
            AND `attr_per` >0) AS CGST, 

            (SELECT GROUP_CONCAT( CONCAT( ' (' ,`attr_per`, '%)Rs.', format(`tax_amount`,2) ) ) AS SGST
            FROM  `view_sales_gst_report` 
            WHERE `sale_id` =  `sma_sales`.`id`
            AND `attr_code` =  'SGST'
            AND `attr_per` >0) AS SGST, 

            (SELECT GROUP_CONCAT( CONCAT( ' (' ,`attr_per`, '%)Rs.', format(`tax_amount`,2) ) ) AS IGST
            FROM  `view_sales_gst_report` 
            WHERE  `sale_id` =  `sma_sales`.`id`
            AND `attr_code` =  'IGST' )AS IGST,

            (SELECT GROUP_CONCAT( CONCAT( ' (' ,`attr_per`, '%)Rs.', format(`tax_amount`,2) ) ) AS VAT
            FROM  `view_sales_gst_report` 
            WHERE  `sale_id` =  `sma_sales`.`id`
            AND `attr_code` =  'VAT' )AS VAT,
            
            (SELECT GROUP_CONCAT( CONCAT( ' (' ,`attr_per`, '%)Rs.', format(`tax_amount`,2) ) ) AS CESS
            FROM  `view_sales_gst_report` 
            WHERE  `sale_id` =  `sma_sales`.`id`
            AND `attr_code` =  'CESS' )AS CESS,

            grand_total + rounding,
           (grand_total - total_tax ) as tax_able_amount,
            (SELECT (GROUP_CONCAT(DISTINCT CONCAT(' ' , format(tax,2),'%'))) as tax_rate  FROM `sma_sale_items` WHERE `sma_sale_items`.`sale_id` = `sma_sales`.`id` ) as tax_rate,
            paid,
            (grand_total + rounding -paid) as balance,
            sma_payments.paid_by,
            payment_status,
            {$this->db->dbprefix('sales')}.id as id", FALSE)
                    ->from('sales')
                    ->join('companies comp', 'sales.customer_id=comp.id', 'left')
                    ->join('sma_payments ', 'sales.id=sma_payments.sale_id', 'left')
                    ->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left')
                    ->group_by('sales.id');
            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->datatables->where('sales.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('sales.created_by', $user);
                    }
                }
            }
            if ($biller) {
                $this->datatables->where('sales.biller_id', $biller);
            }
            if ($customer) {
                $this->datatables->where('sales.customer_id', $customer);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('sales.warehouse_id IN(' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->datatables->like('sales.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->datatables->where($this->db->dbprefix('sales') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            } else {
                $this->datatables->where($this->db->dbprefix('sales') . '.date BETWEEN "' . (date('Y') - 2) . date('-m') . date('-d ') . '00:00:00' . '" and "' . date('Y-m-d H:i:s') . '"');
            }

            if ($gstn_opt) {
                switch ($gstn_opt) {
                    case '-1':
                        $this->datatables->where("comp.gstn_no IS NULL OR comp.gstn_no = '' ");
                        break;

                    case '1':
                        $this->datatables->where("comp.gstn_no IS NOT NULL and comp.gstn_no != '' ");
                        break;

                    default:

                        break;
                }
            }
            if ($gstn_no) {
                $this->datatables->where("comp.gstn_no = '" . $gstn_no . "' ");
            }
            if ($SalesIds) {
                $this->datatables->where('sales.id in (' . $SalesIds . ')');
            }
            echo $this->datatables->generate();
        }
    }

    public function getCountSalesGst() {
        $SalesIds = '';
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
        $gstn_opt = $this->input->get('gstn_opt') ? $this->input->get('gstn_opt') : NULL;
        $gstn_no = $this->input->get('gstn_no') ? $this->input->get('gstn_no') : NULL;
        $hsn_code = $this->input->get('hsn_code') ? $this->input->get('hsn_code') : NULL;
        $max_export_sales = $this->input->get('max_export_sales') ? $this->input->get('max_export_sales') : '0-200'; //0-500
        if (!empty($hsn_code)) {
            $SalesIds = $this->reports_model->getSaleIdByHsn($hsn_code);
        }
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        $this->db->select("sales.id as sale_id,sales.date, sales.reference_no, sales.biller, sales.customer,sales.product_tax as total_tax , 
                IF(comp.gstn_no IS NULL or comp.gstn_no = '', '-', comp.gstn_no) as gstn_no,   comp.address,  comp.city,  comp.phone,  comp.email , 
                sma_sales.grand_total as grand_total, sma_sales.paid as paid,sma_payments.paid_by, sales.payment_status", FALSE)
                ->from('sales')
                ->join('companies comp', 'sales.customer_id=comp.id', 'left')
                ->join('sma_payments ', 'sales.id=sma_payments.sale_id', 'left')
                ->group_by('sales.id')
                ->order_by('sales.date desc');
        if ($this->Owner || $this->Admin) {
            if ($user) {
                $this->db->where('sales.created_by', $user);
            }
        } else {
            if ($this->session->userdata('view_right') == '0') {
                if ($user) {
                    $this->db->where('sales.created_by', $user);
                }
            }
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

        if ($gstn_opt) {
            switch ($gstn_opt) {
                case '-1':
                    $this->db->where("comp.gstn_no IS NULL OR comp.gstn_no = '' ");
                    break;
                case '1':
                    $this->db->where("comp.gstn_no IS NOT NULL and comp.gstn_no != '' ");
                    break;
                default:
                    break;
            }
        }

        if ($gstn_no) {
            $this->db->where("comp.gstn_no = '" . $gstn_no . "' ");
        }
        if ($SalesIds) {
            $this->db->where('sales.id in (' . $SalesIds . ')');
        }

        $q = $this->db->get();
        if ($q->num_rows() > 0) {
            $data = $q->num_rows();

            return $data;
        }

        return FALSE;
    }

    public function purchases_gst_report() {
        $this->sma->checkPermissions('purchases');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('purchases_report')));
        $meta = array('page_title' => lang('purchases_report'), 'bc' => $bc);
        $this->page_construct('reports/purchases_gst', $meta, $this->data);
    }

    function create_image() {
        $this->data['FileName'] = $this->uri->segment(3);
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('payments_report')));
        $meta = array('page_title' => lang('payments_report'), 'bc' => $bc);
        $this->page_construct('reports/view_image_format', $meta, $this->data);
    }

    public function getPurchasesReportC($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('purchases', TRUE);
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $supplier = $this->input->get('supplier') ? $this->input->get('supplier') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $gstn_opt = $this->input->get('gstn_opt') ? $this->input->get('gstn_opt') : NULL;
        $gstn_no = $this->input->get('gstn_no') ? $this->input->get('gstn_no') : NULL;
        $hsn_code = $this->input->get('hsn_code') ? $this->input->get('hsn_code') : NULL;
        $PurchaseIds = '';
        if (!empty($hsn_code)) {
            $PurchaseIds = $this->reports_model->getPurchaseIdByHsn($hsn_code);
        }
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        if ($pdf || $xls || $img) {

            /*
              (SELECT  CONCAT('(',ifnull( `sma_purchase_items_tax`.`attr_per`,0),'%)Rs.',ifnull(format(sum(`sma_purchase_items_tax`.`tax_amount`),2),0)) FROM   `sma_purchase_items_tax` WHERE  `sma_purchase_items_tax`.`attr_code` = 'CGST' and `sma_purchase_items_tax`.`purchase_id` = `sma_purchases`.`id`) as CGST,
              (SELECT CONCAT('(',ifnull( `sma_purchase_items_tax`.`attr_per`,0),'%)Rs.',ifnull(format(sum(`sma_purchase_items_tax`.`tax_amount`),2),0)) FROM   `sma_purchase_items_tax` WHERE  `sma_purchase_items_tax`.`attr_code` = 'SGST' and `sma_purchase_items_tax`.`purchase_id` = `sma_purchases`.`id`) as SGST,
              (SELECT  CONCAT('(',ifnull( `sma_purchase_items_tax`.`attr_per`,0),'%)Rs.',ifnull(format(sum(`sma_purchase_items_tax`.`tax_amount`),2),0)) FROM   `sma_purchase_items_tax` WHERE  `sma_purchase_items_tax`.`attr_code` = 'IGST' and `sma_purchase_items_tax`.`purchase_id` = `sma_purchases`.`id`) as IGST,
             */

            $this->db->select("" . $this->db->dbprefix('purchases') . ".date, reference_no, " . $this->db->dbprefix('warehouses') . ".name as wname, supplier,IF(comp.gstn_no IS NULL or comp.gstn_no = '', '-', comp.gstn_no) as gstn_no,
            (SELECT  IF(sma_purchase_items.hsn_code ='' OR sma_purchase_items.hsn_code is null , '', (GROUP_CONCAT( CONCAT(sma_purchase_items.hsn_code,' (',FORMAT(sma_purchase_items.quantity,2),')')SEPARATOR ',') ) )
              as hsn_code FROM   `sma_purchase_items` WHERE   `sma_purchase_items`.`purchase_id` = `sma_purchases`.`id`) as hsn_code,
              cgst AS CGST, sgst AS SGST, igst AS IGST, grand_total, (grand_total - total_tax ) as tax_able_amount, 
 (SELECT (GROUP_CONCAT(DISTINCT CONCAT(' ' , format(tax,2),'%'))) as tax_rate  FROM `sma_purchase_items` WHERE `sma_purchase_items`.`purchase_id` = `sma_purchases`.`id` ) as tax_rate,(SELECT  CONCAT(format(sum(item_tax),2), ' Rs')  FROM `sma_purchase_items` WHERE `sma_purchase_items`.`purchase_id` = `sma_purchases`.`id` ) as tax_amt, paid, (grand_total-paid) as balance, {$this->db->dbprefix('purchases')}.status, {$this->db->dbprefix('purchases')}.id as id", FALSE)->from('purchases')->join('companies comp', 'purchases.supplier_id=comp.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left');

// paid, " . $this->db->dbprefix('purchases') . ".status", FALSE)->from('purchases')->join('companies comp', 'purchases.supplier_id=comp.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left')->group_by('purchases.id')->order_by('purchases.date desc');

            if ($this->session->userdata('view_right') == '0') {
                if ($user) {
                    $this->db->where('purchases.created_by', $user);
                }
            }
            if ($supplier) {
                $this->db->where('purchases.supplier_id', $supplier);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('purchases.warehouse_id IN(' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->db->like('purchases.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                //$this->db->where(DATE('.$this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('purchases') . '.date) >= "' . $start_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('purchases') . '.date) <= "' . $end_date . '"');
            }
            if ($gstn_opt) {
                switch ($gstn_opt) {
                    case '-1':
                        $this->db->where("comp.gstn_no IS NULL OR comp.gstn_no = '' ");
                        break;

                    case '1':
                        $this->db->where("comp.gstn_no IS NOT NULL and comp.gstn_no != '' ");
                        break;

                    default:

                        break;
                }
            }
            if ($gstn_no) {
                $this->db->where("comp.gstn_no = '" . $gstn_no . "' ");
            }

            if ($PurchaseIds) {
                $PurchaseIds = $this->reports_model->getPurchaseIdByHsn($hsn_code);
                $this->db->where('purchases.id in (' . $PurchaseIds . ')');
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

                $this->excel->getActiveSheet()->getStyle("A1:P1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:P1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'GST Purchases Report');
                $this->excel->getActiveSheet()->setTitle(lang('purchase_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('warehouse'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('supplier'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('gstn'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('hsn_code'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('CGST'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('SGST'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('IGST'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('Taxable_Amount'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('GST_Rate'));
                $this->excel->getActiveSheet()->SetCellValue('M2', lang('Tax Amount (Rs)'));

                $this->excel->getActiveSheet()->SetCellValue('N2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('O2', lang('balance'));
                $this->excel->getActiveSheet()->SetCellValue('P2', lang('status'));

                $row = 3;
                $total = 0;
                $total_tax = 0;
                $paid = 0;
                $tax_amt = 0;
                $balance = 0;
                $cgst = 0;
                $sgst = 0;
                $igst = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->wname);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->supplier);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->gstn_no);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->hsn_code);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->CGST);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->SGST);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->IGST);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $data_row->tax_able_amount);
                    $this->excel->getActiveSheet()->SetCellValue('L' . $row, $data_row->tax_rate);
                    $this->excel->getActiveSheet()->SetCellValue('M' . $row, $data_row->tax_amt);
                    $this->excel->getActiveSheet()->SetCellValue('N' . $row, $data_row->paid);
                    $this->excel->getActiveSheet()->SetCellValue('O' . $row, ($data_row->grand_total - $data_row->paid));
                    $this->excel->getActiveSheet()->SetCellValue('P' . $row, $data_row->status);

                    //$cgst += $data_row->CGST;
                    // $sgst += $data_row->SGST;
                    // $igst += $data_row->IGST;
                    $total += $data_row->grand_total;
                    $total_tax += $data_row->tax_able_amount;
                    $tax_amt += $data_row->tax_amt;
                    $paid += $data_row->paid;
                    $balance += ($data_row->grand_total - $data_row->paid);
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("H" . $row . ":I" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                //$this->excel->getActiveSheet()->SetCellValue('G' . $row, $cgst);
                //$this->excel->getActiveSheet()->SetCellValue('H' . $row, $sgst);
                //$this->excel->getActiveSheet()->SetCellValue('I' . $row, $igst);
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $total);
                $this->excel->getActiveSheet()->SetCellValue('K' . $row, $total_tax);
                $this->excel->getActiveSheet()->SetCellValue('M' . $row, $tax_amt);
                $this->excel->getActiveSheet()->SetCellValue('N' . $row, $paid);
                $this->excel->getActiveSheet()->SetCellValue('O' . $row, $balance);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('P')->setWidth(20);

                $filename = 'GST_purchase_report';
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/purchase_gst_report.pdf', __FILE__));
                    redirect("reports/create_image/purchase_gst_report.pdf");
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            /*
              (SELECT CONCAT('(',ifnull( `sma_purchase_items_tax`.`attr_per`,0),'%) Rs.',ifnull(format(sum(`sma_purchase_items_tax`.`tax_amount`),2),0)) FROM   `sma_purchase_items_tax` WHERE  `sma_purchase_items_tax`.`attr_code` = 'CGST' and `sma_purchase_items_tax`.`purchase_id` = `sma_purchases`.`id`) as CGST,
              (SELECT CONCAT('(',ifnull( `sma_purchase_items_tax`.`attr_per`,0),'%) Rs.',ifnull(format(sum(`sma_purchase_items_tax`.`tax_amount`),2),0)) FROM   `sma_purchase_items_tax` WHERE  `sma_purchase_items_tax`.`attr_code` = 'SGST' and `sma_purchase_items_tax`.`purchase_id` = `sma_purchases`.`id`) as SGST,
              (SELECT CONCAT('(',ifnull( `sma_purchase_items_tax`.`attr_per`,0),'%) Rs.',ifnull(format(sum(`sma_purchase_items_tax`.`tax_amount`),2),0)) FROM   `sma_purchase_items_tax` WHERE  `sma_purchase_items_tax`.`attr_code` = 'IGST' and `sma_purchase_items_tax`.`purchase_id` = `sma_purchases`.`id`) as IGST,
             *  */

            $this->load->library('datatables');
            $this->datatables->select("DATE_FORMAT({$this->db->dbprefix('purchases')}.date, '%Y-%m-%d %T') as date, reference_no, {$this->db->dbprefix('warehouses')}.name as wname, supplier,IF(comp.gstn_no IS NULL or comp.gstn_no = '', '-', comp.gstn_no) as gstn_no,
            (SELECT  IF(sma_purchase_items.hsn_code ='' OR sma_purchase_items.hsn_code is null , '', (GROUP_CONCAT( CONCAT(sma_purchase_items.hsn_code,' (',FORMAT(sma_purchase_items.quantity,2),')')SEPARATOR '<br>') ) )
              as hsn_code FROM   `sma_purchase_items` WHERE   `sma_purchase_items`.`purchase_id` = `sma_purchases`.`id`) as hsn_code,
              cgst AS CGST, sgst AS SGST, igst AS IGST, grand_total, (grand_total - total_tax ) as tax_able_amount,
            (SELECT (GROUP_CONCAT(DISTINCT CONCAT(' ' , format(tax,2),'%'))) as tax_rate  FROM `sma_purchase_items` WHERE `sma_purchase_items`.`purchase_id` = `sma_purchases`.`id` ) as tax_rate,
 (SELECT  CONCAT(format(sum(item_tax),2))  FROM `sma_purchase_items` WHERE `sma_purchase_items`.`purchase_id` = `sma_purchases`.`id` ) as tax_amt,
  paid, (grand_total-paid) as balance, {$this->db->dbprefix('purchases')}.status, {$this->db->dbprefix('purchases')}.id as id", FALSE)->from('purchases')->join('companies comp', 'purchases.supplier_id=comp.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left');
            // ->group_by('purchases.id');
            if ($this->session->userdata('view_right') == '0') {
                if ($user) {
                    $this->datatables->where('purchases.created_by', $user);
                }
            }
            if ($supplier) {
                $this->datatables->where('purchases.supplier_id', $supplier);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('purchases.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->datatables->like('purchases.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->datatables->where('DATE(' . $this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            if ($gstn_opt) {
                switch ($gstn_opt) {
                    case '-1':
                        $this->datatables->where("comp.gstn_no IS NULL OR comp.gstn_no = '' ");
                        break;

                    case '1':
                        $this->datatables->where("comp.gstn_no IS NOT NULL and comp.gstn_no != '' ");
                        break;

                    default:

                        break;
                }
            }
            if ($gstn_no) {
                $this->datatables->where("comp.gstn_no = '" . $gstn_no . "' ");
            }

            if ($PurchaseIds) {

                $this->datatables->where('purchases.id in (' . $PurchaseIds . ')');
            }
            echo $this->datatables->generate();
        }
    }

    public function sales_tax_report_ajax() {

        $this->sma->checkPermissions('sales', TRUE);
        $param = array();
        $param['product'] = $this->input->get('product') ? $this->input->get('product') : NULL;
        $param['user'] = $this->input->get('user') ? $this->input->get('user') : NULL;
        $param['customer'] = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $param['biller'] = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $param['warehouse'] = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $param['reference_no'] = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $param['start_date'] = $this->input->get('start_date') ? date("Y-m-d H:i:s", $this->input->get('start_date')) : NULL;
        $param['end_date'] = $this->input->get('end_date') ? date("Y-m-d H:i:s", $this->input->get('end_date')) : NULL;
        $param['serial'] = $this->input->get('serial') ? $this->input->get('serial') : NULL;
        $param['gstn_opt'] = $this->input->get('gstn_opt') ? $this->input->get('gstn_opt') : NULL;
        $param['gstn_no'] = $this->input->get('gstn_no') ? $this->input->get('gstn_no') : NULL;
        $param['hsn_code'] = $this->input->get('hsn_code') ? $this->input->get('hsn_code') : NULL;

        $taxReport = $this->reports_model->salesTaxReport($param);
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['sales'] = $taxReport;
        $this->load->view($this->theme . 'reports/sales_tax_report_model', $this->data);
    }

    public function purchase_tax_report_ajax() {
        $this->sma->checkPermissions('purchases', TRUE);
        $param = array();
        $param['user'] = $this->input->get('user') ? $this->input->get('user') : NULL;
        $param['supplier'] = $this->input->get('supplier') ? $this->input->get('supplier') : NULL;
        $param['warehouse'] = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $param['reference_no'] = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $param['start_date'] = $this->input->get('start_date') ? date("Y-m-d H:i:s", $this->input->get('start_date')) : NULL;
        $param['end_date'] = $this->input->get('end_date') ? date("Y-m-d H:i:s", $this->input->get('end_date')) : NULL;
        $param['gstn_opt'] = $this->input->get('gstn_opt') ? $this->input->get('gstn_opt') : NULL;
        $param['gstn_no'] = $this->input->get('gstn_no') ? $this->input->get('gstn_no') : NULL;
        $param['hsn_code'] = $this->input->get('hsn_code') ? $this->input->get('hsn_code') : NULL;

        $taxReport = $this->reports_model->purchaseTaxReport($param);
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['sales'] = $taxReport;
        $this->load->view($this->theme . 'reports/purchases_tax_report_model', $this->data);
    }

    public function sales_tax_report() {
        $taxReport = $this->reports_model->salesTaxReport();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['sales'] = $taxReport;
        $this->page_construct('reports/sales_tax_report', $meta, $this->data);
    }

    public function warehouse_sales() {
        $this->sma->checkPermissions('sales');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        //$this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        // $this->data['billers'] = $this->site->getAllCompanies('biller');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => 'Warehouse ' . lang('sales_report')));
        $meta = array('page_title' => 'Warehouse ' . lang('sales_report'), 'bc' => $bc);
        $this->page_construct('reports/warehouse_sales', $meta, $this->data);
    }

    function getWarehouseSalesReport($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('sales', TRUE);

        $start_date = $start_dmy = $this->input->get('start_date') ? $this->input->get('start_date') : date('d/m/Y');
        $end_date = $end_dmy = $this->input->get('end_date') ? $this->input->get('end_date') : date('d/m/Y');
        $report_type = $this->input->get('report_type') ? $this->input->get('report_type') : 1;
        $passwarehouse = ($this->input->get('warehouse')) ? str_replace("_", ",", $this->input->get('warehouse')) : '';


        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        if ($pdf || $xls || $img) {
            if ($this->input->get('warehouse')) {
                $getwarehouse = str_replace("_", ",", $this->input->get('warehouse'));
            }
            // "sum(`grand_total`) as net_sale, "
            $this->db->select($this->db->dbprefix('warehouses') .
                            ".id as warehouse_id, " . $this->db->dbprefix('warehouses') .
                            ".name as warehouse, "
                            . "sum(`total`) as net_total , "
                            . "sum(`total_discount`) discount, "
                            . "sum(`rounding`) as rounding, "
                            . "sum(`shipping`) as shipping, "
                            . "sum(`total`) as net_sale, "
                            . "sum(`total_tax`) as tax, "
                            . "count(`reference_no`) total_bills ", FALSE)
                    ->from('sales')
                    ->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');
            // ->group_by('sales.warehouse_id');


            if ($start_date) {
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" AND "' . $end_date . '"');
            }

            if ($getwarehouse) {
                $this->db->where('sales.warehouse_id IN (' . $getwarehouse . ')');
            }

            if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
                $this->db->where('sales.created_by', $user);
            }
            $this->db->where('sales.sale_status!="returned"');
            $this->db->group_by('sales.warehouse_id');

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
                $this->excel->getActiveSheet()->mergeCells('A1:I1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Warehouse Sales Reports');

                $this->excel->getActiveSheet()->setTitle(lang('warehouse_sales_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Branch Name (Warehouses)'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Gross_Sale (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Discount (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Due Amount (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Return  Amount (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Net_Sale (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Cash (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('Without Cash (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Total_Tax (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Total Invoices'));
//                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Items Sold'));

                $row = 3;
                $total_gross_sale = 0;
                $total_discount = 0;
                $total_net_sale = 0;
                $calculate_total_tax = 0;
                $cal_total_bills = 0;
                $total_sold_items = 0;
                $total_due = 0;
                $total_return = 0;
                $total_sales = 0;
                $total_withcash = 0;
                $total_withoutcash = 0;
                foreach ($data as $data_row) {
                    $WithCash = 0;
                    $WithoutCash = 0;
                    $Warehouse_id = $data_row->warehouse_id;
                    $SqlCash = "SELECT p.amount, p.paid_by FROM `sma_sales` s inner join `sma_payments` p on s.id=p.sale_id where s.warehouse_id='$Warehouse_id' "; //and  p.paid_by='cash'
                    if ($start_date) {
                        $SqlCash .= " and DATE(s.date) BETWEEN '$start_date' AND '$end_date' ";
                    }
                    $Rescash = $this->db->query($SqlCash);
                    foreach ($Rescash->result() as $rowcash) {
                        if ($rowcash->paid_by == 'cash') {
                            $WithCash += $rowcash->amount;
                        } else {
                            $WithoutCash += $rowcash->amount;
                        }
                    }
                    $net_total = $data_row->net_total;

                    $gross_sale = $data_row->net_total + $data_row->discount + $data_row->tax;

                    /* --- 18-03-19 --- */
                    $duesales = $this->reports_model->getreport($start_date, $end_date, 'due', $data_row->warehouse_id);
                    $returnsales = $this->reports_model->getreport($start_date, $end_date, 'return', $data_row->warehouse_id);
                    $partialamt = $this->reports_model->getreportbalance($start_date, $end_date, $data_row->warehouse_id);
                    $pendingamount = $this->reports_model->getreport($start_date, $end_date, 'pending', $data_row->warehouse_id);
                    $tax = $data_row->tax + $returnsales->tax;
                    $net_sale = ($data_row->net_sale + $data_row->shipping + $data_row->rounding ) + ($returnsales->net_sale + $returnsales->rounding) + $tax;
                    $discount = $data_row->discount + $returnsales->total_discount;

                    $total_dueamt = $duesales->total + $duesales->total_discount + $partialamt + $pendingamount->total;
                    $total_returnAmt = str_replace('-', '', $returnsales->total); // + str_replace('-', '', $returnsales->total_discount);
                    $total_sales = $data_row->Total_sales + $total_returnAmt;
                    $total_netamt = $total_sales - $data_row->discount - $total_dueamt - $total_returnAmt;
                    /* ---- 18-03-19 --- */

                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->warehouse);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, round($gross_sale));
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, round($discount));
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, round($total_dueamt));
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, round($total_returnAmt));
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, round($net_sale));
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, round($WithCash));
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, round($WithoutCash));
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, round($tax));
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, round($data_row->total_bills));
                    //$this->excel->getActiveSheet()->SetCellValue('I' . $row, round($data_row->sold_items));


                    $warehouses[$data_row->warehouse_id] = $data_row->warehouse;

                    $total_gross_sale += $gross_sale;
                    $total_discount += $discount;
                    $total_net_sale += $net_sale;
                    $total_total_tax += $tax;
                    $total_total_bills += $data_row->total_bills;
                    // $total_sold_items += $data_row->sold_items;
                    $total_due += $total_dueamt;
                    $total_retrun += $total_returnAmt;
                    $total_withcash += $WithCash;
                    $total_withoutcash += $WithoutCash;
                    $row++;
                }//end foreach.

                $total_row = $row++;

                $this->excel->getActiveSheet()->SetCellValue('A' . $total_row, 'Total');
                $this->excel->getActiveSheet()->SetCellValue('B' . $total_row, round($total_gross_sale));
                $this->excel->getActiveSheet()->SetCellValue('C' . $total_row, round($total_discount));
                $this->excel->getActiveSheet()->SetCellValue('D' . $total_row, round($total_due));
                $this->excel->getActiveSheet()->SetCellValue('E' . $total_row, round($total_retrun));
                $this->excel->getActiveSheet()->SetCellValue('F' . $total_row, round($total_net_sale));
                $this->excel->getActiveSheet()->SetCellValue('G' . $total_row, round($total_withcash));
                $this->excel->getActiveSheet()->SetCellValue('H' . $total_row, round($total_withoutcash));
                $this->excel->getActiveSheet()->SetCellValue('I' . $total_row, round($total_total_tax));
                $this->excel->getActiveSheet()->SetCellValue('J' . $total_row, round($total_total_bills));
                //$this->excel->getActiveSheet()->SetCellValue('I' . $total_row, round($total_sold_items));

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
                // $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);

                if ($report_type == 1) {
                    //Warehouse Balance Items Reports   

                    $wdata = $this->reports_model->warehouseProductsStock($passwarehouse);

                    $whproducts = $wdata['products'];
                    $warehouses = $wdata['warehouse'];

                    $wcount = count($warehouses);
                    $colw = 'A';
                    for ($i = -1; $i <= $wcount; $i++) {
                        $colw++;
                    }
                    //Report Heading
                    $row2 = $total_row + 1;
                    $this->excel->getActiveSheet()->mergeCells('A' . $row2 . ":$colw" . $row2);
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row2, 'Daily stock warehouses comparsion date:' . date('d/m/Y'));
                    $styleTitle = array(
                        'alignment' => array(
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'font' => array(
                            'color' => array('rgb' => 'FF0000'),
                            'size' => 12,
                            'name' => 'Verdana'
                        )
                    );
                    $this->excel->getActiveSheet()->getStyle('A' . $row2 . ":$colw" . $row2)->applyFromArray($styleTitle);
                    $this->excel->getActiveSheet()->getRowDimension($row2)->setRowHeight(40);
                    $this->excel->getActiveSheet()->getStyle('A1:' . $colw . '1')->applyFromArray($styleTitle);
                    $this->excel->getActiveSheet()->getRowDimension(1)->setRowHeight(40);

                    //Report Column Title
                    $row2++;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row2, 'Product Name');
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row2, 'Product Code');
                    if (is_array($warehouses)) {
                        $col_t = 'B';
                        foreach ($warehouses as $warehouse) {
                            $col_t++;
                            $this->excel->getActiveSheet()->SetCellValue($col_t . $row2, $warehouse);
                        }//end foreach
                    }//end if
                    $col_t++;
                    $this->excel->getActiveSheet()->SetCellValue($col_t . $row2, 'Total Stocks');

                    $styleHeading = array(
                        'alignment' => array(
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'font' => array(
                            'color' => array('rgb' => '000000'),
                        ),
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN)
                        )
                    );
                    $this->excel->getActiveSheet()->getStyle('A' . $row2 . ":$col_t" . $row2)->applyFromArray($styleHeading);
                    $this->excel->getActiveSheet()->getStyle('A2:G2')->applyFromArray($styleHeading);
                    $this->excel->getActiveSheet()->getStyle('A' . $total_row . ":G" . $total_row)->applyFromArray($styleHeading);


                    //Report Data Items
                    if (is_array($whproducts)) {

                        foreach ($whproducts as $pid => $wsitems) {
                            $row2++;
                            $this->excel->getActiveSheet()->SetCellValue('A' . $row2, $wsitems['name']);
                            $this->excel->getActiveSheet()->SetCellValue('B' . $row2, $wsitems['code']);
                            if (is_array($warehouses)) {
                                $col_i = 'B';
                                $stock = 0;
                                foreach ($warehouses as $wid => $warehouse) {
                                    $col_i++;
                                    $qty = ($wsitems['wpq'][$wid]) ? number_format($wsitems['wpq'][$wid]) : 0;

                                    $this->excel->getActiveSheet()->SetCellValue($col_i . $row2, $qty);
                                    $stock += $qty;
                                }//end foreach                            
                            }//end if
                            $col_i++;
                            $this->excel->getActiveSheet()->SetCellValue($col_i . $row2, $stock);
                        }//end foreach.
                    }//end if.               
                }//end if.    
                elseif ($report_type == 2) {
                    //Warehouse Sales Items Reports 
                    $wcount = count($warehouses);
                    $colw = 'A';
                    for ($i = -1; $i <= $wcount; $i++) {
                        $colw++;
                    }
                    //Report Heading
                    $row2 = $total_row + 1;
                    $this->excel->getActiveSheet()->mergeCells('A' . $row2 . ":$colw" . $row2);
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row2, "Sold-out Warehouse Items Reports From  From $start_dmy to $end_dmy");
                    $styleTitle = array(
                        'alignment' => array(
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'font' => array(
                            'color' => array('rgb' => 'FF0000'),
                            'size' => 12,
                            'name' => 'Verdana'
                        )
                    );
                    $this->excel->getActiveSheet()->getStyle('A' . $row2 . ":$colw" . $row2)->applyFromArray($styleTitle);
                    $this->excel->getActiveSheet()->getRowDimension($row2)->setRowHeight(40);
                    $this->excel->getActiveSheet()->getStyle('A1:G1')->applyFromArray($styleTitle);
                    $this->excel->getActiveSheet()->getRowDimension(1)->setRowHeight(40);

                    //Report Column Title
                    $row2++;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row2, 'Product Name');
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row2, 'Product Code');
                    if (is_array($warehouses)) {
                        $col_t = 'B';
                        foreach ($warehouses as $warehouse) {
                            $col_t++;
                            $this->excel->getActiveSheet()->SetCellValue($col_t . $row2, $warehouse);
                        }//end foreach
                    }//end if
                    $col_t++;
                    $this->excel->getActiveSheet()->SetCellValue($col_t . $row2, 'Total');

                    $styleHeading = array(
                        'alignment' => array(
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'font' => array(
                            'color' => array('rgb' => '000000'),
                        ),
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN)
                        )
                    );
                    $this->excel->getActiveSheet()->getStyle('A' . $row2 . ":$col_t" . $row2)->applyFromArray($styleHeading);
                    $this->excel->getActiveSheet()->getStyle('A2:G2')->applyFromArray($styleHeading);
                    $this->excel->getActiveSheet()->getStyle('A' . $total_row . ":G" . $total_row)->applyFromArray($styleHeading);

                    $warehouseSalesItems = $this->reports_model->warehouseSalesItems($start_date, $end_date, $passwarehouse);
                    //Report Data Items
                    if (is_array($warehouseSalesItems)) {

                        foreach ($warehouseSalesItems as $pid => $wsitems) {
                            $row2++;
                            $this->excel->getActiveSheet()->SetCellValue('A' . $row2, $wsitems['name']);
                            $this->excel->getActiveSheet()->SetCellValue('B' . $row2, $wsitems['code']);
                            if (is_array($warehouses)) {
                                $col_i = 'B';
                                $wsi_total = 0;
                                foreach ($warehouses as $wid => $warehouse) {
                                    $col_i++;
                                    $wsiq = ($wsitems['wh'][$wid]) ? $wsitems['wh'][$wid] : 0;
                                    $this->excel->getActiveSheet()->SetCellValue($col_i . $row2, $wsiq);
                                    $wsi_total += $wsiq;
                                }//end foreach                            
                            }//end if
                            $col_i++;
                            $this->excel->getActiveSheet()->SetCellValue($col_i . $row2, $wsi_total);
                        }//end foreach.
                    }//end if.
                }//end if.        

                $filename = 'sales_warehouse_report';
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/sales_warehouse_report.pdf', __FILE__));
                    redirect("reports/create_image/sales_warehouse_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
            $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

            if ($this->input->get('warehouse')) {
                $getwarehouse = str_replace("_", ",", $this->input->get('warehouse'));
            }
            //net_sale = grand_total  
            //    sum(s.`grand_total`) as net_sale,
            $sql = "SELECT w.`id` as warehouse_id,  w.`name` as warehouse , 
                sum(s.`total`) net_total, 
                sum(s.`total_discount`) as  discount, 
                sum(s.`total`) as net_sale,
                sum(s.`rounding`) as rounding,
                sum(s.`shipping`) as shipping,
                sum(s.`total_tax`) as tax, 
                count(s.`reference_no`) total_bills
                    
                    FROM `sma_sales` s 
                    LEFT JOIN `sma_warehouses` w ON s.`warehouse_id` = w.`id` ";

            $where = '';

            if ($start_date) {
                $start_date = $this->sma->fld($start_date);
                $end_date = $this->sma->fld($end_date);
                $where = " WHERE DATE(date) BETWEEN '$start_date' AND '$end_date' ";
            }

            if ($this->input->get('warehouse')) {
                $where .= " AND s.`warehouse_id` IN ({$getwarehouse})";
            }

            if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
                $where .= " AND s.`created_by` =" . $user;
            }


            $sql .= $where . " and sale_status !='returned' GROUP BY s.`warehouse_id`";

            $q = $this->db->query($sql);

            $num = $q->num_rows();

            echo '<table id="SlRData" class="table table-bordered table-hover table-striped table-condensed reports-table">
                        <thead>
                        <tr>
                            <th>Branch (Warehouse) Name</th>
                            <th>Gross Sale</th>
                            <th>Discount</th>
                            <th> Due Amount </th>
                            <th> Return Amount </th>
                            <th>Net Sale</th><th>Cash</th><th>Without Cash</th>
                            <th>Total Tax</th>
                            <th>Total Invoice</th>                            
                            <th>Items</th>                             
                        </tr>
                        </thead>
                        <tbody>';

            if ($num > 0) {
                $total_gross_sale = 0;
                $total_discount = 0;
                $total_net_sale = 0;
                $calculate_total_tax = 0;
                $cal_total_bills = 0;
                $total_sold_items = 0;
                $total_due = 0;
                $total_return = 0;
                $total_sales = 0;
                $total_withcash = 0;
                $total_withoutcash = 0;


                foreach ($q->result() as $row) {
                    $WithCash = 0;
                    $WithoutCash = 0;
                    $Warehouse_id = $row->warehouse_id;
                    $SqlCash = "SELECT p.amount, p.paid_by FROM `sma_sales` s inner join `sma_payments` p on s.id=p.sale_id where s.warehouse_id='$Warehouse_id' and p.paid_by!='gift_card'"; //and  p.paid_by='cash' 
                    if ($start_date) {
                        $SqlCash .= " and DATE(s.date) BETWEEN '$start_date' AND '$end_date' ";
                    }
                    $Rescash = $this->db->query($SqlCash);
                    foreach ($Rescash->result() as $rowcash) {
                        if ($rowcash->paid_by == 'cash') {
                            $WithCash += $rowcash->amount;
                        } else {
                            $WithoutCash += $rowcash->amount;
                        }
                    }
                    $net_total = $row->net_total;




                    $gross_sale = $row->net_total + $row->discount + $row->tax;

                    $duesales = $this->reports_model->getreport($start_date, $end_date, 'due', $row->warehouse_id);
                    $returnsales = $this->reports_model->getreport($start_date, $end_date, 'return', $row->warehouse_id);
                    $partialamt = $this->reports_model->getreportbalance($start_date, $end_date, $row->warehouse_id);
                    $pendingamount = $this->reports_model->getreport($start_date, $end_date, 'pending', $row->warehouse_id);
                    $tax = $row->tax + $returnsales->tax;
                    //$net_sale   = $row->net_sale+$returnsales->net_sale+$tax;
                    $net_sale = ($row->net_sale + $row->shipping + $row->rounding) + ($returnsales->net_sale + $returnsales->rounding ) + $tax;

                    $total_dueamt = $duesales->total + $duesales->total_discount + $partialamt + $pendingamount->total; //
                    $total_returnAmt = str_replace('-', '', $returnsales->total); // + str_replace('-', '', $returnsales->total_discount);
                    // $total_returnAmt;
                    $discount = $row->discount + $returnsales->total_discount;
                    $total_sales = $row->Total_sales + $total_returnAmt;

                    echo '<tr> <td>' . $row->warehouse . '</td>';
                    echo '<td>Rs. ' . number_format($gross_sale, 2) . '</td>';
                    echo '<td>Rs. ' . number_format($discount, 2) . '</td>';
                    echo '<td>Rs. ' . number_format($total_dueamt, 2) . '</td>';
                    echo '<td>Rs. ' . (($total_returnAmt != 0) ? number_format($total_returnAmt, 2) : 0) . '</td>';
                    echo '<td>Rs. ' . number_format($net_sale, 2) . '</td>'; // $row->net_sale - $total_dueamt
                    echo '<td>Rs. ' . number_format($WithCash, 2) . '</td>';
                    echo '<td>Rs. ' . number_format($WithoutCash, 2) . '</td>';
                    echo '<td>Rs. ' . number_format($tax, 2) . '</td>';
                    echo '<td>' . number_format($row->total_bills) . '</td>';

                    echo '<td onclick="getsaleitems(\'' . $start_date . '\',\'' . $end_date . '\', \'' . $row->warehouse_id . '\', \'' . $row->warehouse . '\')"><i class="fa fa-list-alt" aria-hidden="true"></i></td></tr>';

                    $total_gross_sale += $gross_sale;
                    $total_discount += $discount;
                    $total_due += $total_dueamt;
                    $total_return += $total_returnAmt;
                    $total_net_sale += $net_sale;
                    $calculate_total_tax += $tax;
                    $cal_total_bills += $row->total_bills;
                    $total_withcash += $WithCash;
                    $total_withoutcash += $WithoutCash;
                    $warehouses[$row->warehouse_id] = $row->warehouse;
                }
            }//end num
            else {
                echo ' <tr><td colspan="7" class="dataTables_empty">No Record Found</td></tr>';
            }
            echo '   </tbody>
                        <tfoot>
                        <tr>
                            <th>Total</th>
                            <th>Rs. ' . number_format($total_gross_sale, 2) . '</th>
                            <th>Rs. ' . number_format($total_discount, 2) . '</th>
                            <th>Rs. ' . number_format($total_due, 2) . '</th>
                            <th>Rs. ' . number_format($total_return, 2) . '</th>
                            <th>Rs. ' . number_format($total_net_sale, 2) . '</th>    
                            <th>Rs. ' . number_format($total_withcash, 2) . '</th>                            
                            <th>Rs. ' . number_format($total_withoutcash, 2) . '</th>                             
                            <th>Rs. ' . number_format($calculate_total_tax, 2) . '</th>
                            <th>' . number_format($cal_total_bills) . '</th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>';

            //Warehouse Balance warehouse Product Stock 
            if ($report_type == 1) {
                $wdata = $this->reports_model->warehouseProductsStock($passwarehouse);

                $wps = $wdata['products'];
                $ws = $wdata['warehouse'];
                $datatable = '<h2>Daily stock warehouses comparsion</h2>';
                $str_wp = 'wpq';
            }//end if.
            //Sold-out Warehouse Items Reports
            elseif ($report_type == 2) {
                $wps = $this->reports_model->warehouseSalesItems($start_date, $end_date, $passwarehouse);

                $ws = $warehouses;
                $datatable = '<h2>Sold-out Warehouse Items Reports</h2>';
                $str_wp = 'wh';
            }//end elseif.

            $datatable .= '<div><table id="warehouses_products" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                <th>Product Name</th>
                <th>Code</th>';

            if (is_array($ws)) {
                foreach ($ws as $whn) {
                    $datatable .= "<th>$whn</th>";
                }
            }

            $datatable .= '<th>Total</th> 
            </tr>
          </thead>
        <tbody>';

            $total = array();
            $totalstock = 0;
            if (is_array($wps)) {
                foreach ($wps as $pid => $wpdata) {

                    $datatableRow = '<tr>               
                <td>' . $wpdata['name'] . '</td>
                <td>' . $wpdata['code'] . '</td>';
                    $stock = 0;
                    if (is_array($ws)) {
                        foreach ($ws as $wid => $whn) {
                            $qty = ($wpdata[$str_wp][$wid]) ? number_format($wpdata[$str_wp][$wid]) : 0;
                            $datatableRow .= '<td align="center">' . $qty . '</td>';
                            $stock += $qty;
                            $total[$whn] += $qty;
                        }//end foreach.
                    }//end if.

                    $datatableRow .= '<td align="center"><b>' . $stock . '</b></td>
            </tr>';

                    if ($stock > 0) {
                        $datatable .= $datatableRow;
                    }
                    $totalstock = $totalstock + $stock;
                }//end foreach
            } //end if

            $datatable .= '</tbody>
        <tfoot>
            <tr>
                <th>Product Name</th>
                <th>Code</th>';
            if (is_array($ws)) {
                foreach ($ws as $whn) {
                    $datatable .= "<th class='text-center'>$total[$whn]</th>";
                }
            }
            $datatable .= '<th class="text-center">' . $totalstock . '</th>
            </tr>
        </tfoot>
    </table></div>';


            echo "<div>" . $datatable . "</div>";
        }
    }

    public function warehouse_sales_items() {
        $start_date = $start_dmy = $this->input->get('start_date') ? $this->input->get('start_date') : date('d/m/Y') . ' 00:00';
        $end_date = $end_dmy = $this->input->get('end_date') ? $this->input->get('end_date') : date('d/m/Y H:i');
        $date = $_GET['warehouse'];

        if (empty($date))
            return FALSE;

        //$sale_data = $this->reports_model->getDailySalesItems($date);
        $sale_data = $this->reports_model->getDailyWareSalesItems($date);
        ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Code</th>
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
                    foreach ($sale_data as $key => $item) {
                        ?>
                        <tr>
                            <td><?= ++$i ?></td>
                            <td><?= $item->product_name ?></td>
                            <td><?= $item->product_code ?></td>
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

    public function get_sales_items() {
        $start_date = $_GET['startdate'];
        $end_date = $_GET['enddata'];
        $warehouse_id = $_GET['werehouse'];

        $sale_data = $this->reports_model->getSalesItems($start_date, $end_date, $warehouse_id);
        ?>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Code</th>
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
                    $totalItems = $totalTax = $totaldiscount = $total = 0;
                    foreach ($sale_data as $key => $item) {
                        if ($item->qty <= 0)
                            continue;

                        $totalItems += $item->qty;
                        $totalTax += $item->tax;
                        $totaldiscount += $item->discount;
                        $total += $item->total;
                        ?>
                        <tr>
                            <td><?= ++$i ?></td>
                            <td><?= $item->product_name ?></td>
                            <td><?= $item->product_code ?></td>
                            <td><?= $this->sma->formatMoney($item->net_unit_price) ?></td>
                            <td><?= $item->qty ?></td>
                            <td><?= $item->unit ?></td>
                            <td><?= $item->tax_rate ? number_format($item->tax_rate, 2) : 0; ?>%</td>
                            <td><?= $this->sma->formatMoney($item->tax) ?></td>
                            <td><?= $this->sma->formatMoney($item->discount) ?></td>
                            <td><?= $this->sma->formatMoney($item->total) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4">Totals</th>
                        <th><?= number_format($totalItems, 2) ?></th> 
                        <th>(Items)</th> 
                        <th></th> 
                        <th><?= $this->sma->formatMoney($totalTax) ?></th> 
                        <th><?= $this->sma->formatMoney($totaldiscount) ?></th> 
                        <th><?= $this->sma->formatMoney($total) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php
    }

    function sales_person_report() {
        $this->sma->checkPermissions('reports');
        $this->data['sales_staff'] = $this->site->getCompanyDetailsByGroupID(5);
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Report_Sales_Person')));
        $meta = array('page_title' => lang('Report_Sales_Person'), 'bc' => $bc);
        $this->page_construct('reports/sales_person_report', $meta, $this->data);
    }

    function get_sales_person_report($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('reports', TRUE);
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $sales_person = $this->input->get('sales_person') ? $this->input->get('sales_person') : NULL;

        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        if ($pdf || $xls || $img) {

            $this->db->select("sma_sales.seller_id, companies.name as seller, sum(sma_sales.grand_total + sma_sales.rounding ) as grand_total, count(sma_sales.id) as total_sales, sum(sma_sales.total_items) as no_of_items, sum(sma_sales.total_discount) as total_discount, sum(sma_sales.total_tax) as total_tax")
                    ->from('companies')
                    ->join('sales', 'companies.id=sales.seller_id', 'inner')
                    ->where('companies.group_id', 5)
                    ->group_by('sma_sales.seller_id');

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->db->where('sales.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('sales.created_by', $user);
                    }
                }
            }

            if ($sales_person) {
                $this->db->where('sma_sales.seller_id', $sales_person);
            }

            if ($start_date) {
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Sales Person Report ');
                $this->excel->getActiveSheet()->setTitle(lang('sales_person_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Seller'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Sales_amount'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Total_sales'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('No_of_sale_items'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Total_discount'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Total_tax'));

                $row = 3;
                $total = 0;
                $total_sales = 0;
                $no_of_items = 0;
                $total_discount = 0;
                $total_tax = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->seller);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->total_sales);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->no_of_items);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->total_discount);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->total_tax);

                    $total += $data_row->grand_total;
                    $total_sales += $data_row->total_sales;
                    $no_of_items += $data_row->no_of_items;
                    $total_discount += $data_row->total_discount;
                    $total_tax += $data_row->total_tax;
                    $row++;
                }
                //$this->excel->getActiveSheet()->getStyle("B" . $row . ":F" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $total);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $total_sales);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $no_of_items);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $total_discount);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, round($total_tax, 2));


                $filename = 'sales_person_report';
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/sales_person_report.pdf', __FILE__));
                    redirect("reports/create_image/sales_person_report.pdf");
                    ;
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            $this->load->library('datatables');
            $this->datatables->select("sma_sales.seller_id, companies.name as seller, sum(sma_sales.grand_total + sma_sales.rounding) as grand_total, count(sma_sales.id) as total_sales, sum(sma_sales.total_items) as no_of_items, sum(sma_sales.total_discount) as total_discount, sum(sma_sales.total_tax) as total_tax")
                    ->from('companies')
                    ->join('sales', 'companies.id=sales.seller_id', 'inner')
                    ->where('companies.group_id', 5)
                    ->group_by('sma_sales.seller_id');

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->datatables->where('sma_sales.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('sma_sales.created_by', $user);
                    }
                }
            }
            if ($sales_person) {
                $this->datatables->where('sma_sales.seller_id', $sales_person);
            }
            if ($start_date) {
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            //$detail_link1 = anchor('reports/view_modal_sales_person/$1', '<i class="fa fa-view"></i> ' . lang('view_sale'));
            $detail_link1 = anchor('reports/view_modal_sales_person/$1', '<i class="fa fa-search"></i> ' . lang('view_sale'), 'data-toggle="modal" data-target="#myModal"');
            $detail_link2 = anchor('reports/view_modal_sales_person_items/$1', '<i class="fa fa-search"></i> ' . lang('view_sale_items'), 'data-toggle="modal" data-target="#myModal"');
            $action = '<div class="text-center"><div class="btn-group text-left">'
                    . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
                    . lang('actions') . ' <span class="caret"></span></button>
            <ul class="dropdown-menu pull-right" role="menu">
                    <li>' . $detail_link1 . '</li>
                    <li>' . $detail_link2 . '</li>
            </ul>
        </div></div>';

            $this->datatables->add_column("Actions", $action, 'sma_sales.seller_id');
            echo $this->datatables->generate();
        }
    }

    public function view_modal_sales_person_items($id = null) {
        $this->sma->checkPermissions('reports', true);
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }
        $this->data['SALEDATA'] = $this->reports_model->getSaleItemsBySalesPerson($id);
        $this->load->view($this->theme . 'reports/view_modal_sales_person_items', $this->data);
    }

    public function view_modal_sales_person($id = null) {
        $this->sma->checkPermissions('reports', true);
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }
        $this->data['SALEDATA'] = $this->reports_model->getSaleBySalesPerson($id);
        $this->load->view($this->theme . 'reports/view_modal_sales_person', $this->data);
    }

    public function restbutton() {
        redirect($_SERVER["HTTP_REFERER"]);
    }

    public function daily_sales_items_print() {
        $date = $_GET['date'];
        if (empty($date))
            return FALSE;
        $sale_data = $this->reports_model->getDailySalesItems($date);
        ?>
        <div class="table-responsive">
            <font size="4px" face="Times New Roman" >
            <table class="table table-bordered" >
                <thead>
                    <tr id="tr_data">
                        <th>#</th>
                        <th  style="width:25px;">Product Name</th>
                       <!--  <th>Code</th> -->
                        <th>Price</th>
                        <th  >Qty</th>
                       <!--  <th>Units</th> -->
                      <!--   <th>Tax Rate</th> -->
                        <th>Tax</th>
                        <th>Disc</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sale_data as $key => $item) { ?>
                        <tr id="tr_get">
                            <td><?= ++$i ?></td>
                            <td style="width:25px;"><?= $item->product_name ?></td>
                        <!--     <td><?= $item->product_code ?></td> -->
                            <td><?= $this->sma->formatMoney($item->net_unit_price) ?></td>
                            <td><?= number_format($item->qty, 2) ?></td>
                           <!--  <td><?= $item->unit ?></td> -->
                          <!--   <td><?= $item->tax_rate ? number_format($item->tax_rate, 2) : 0; ?>%</td> -->
                            <td><?= $this->sma->formatMoney($item->tax) ?><br>(<?= $item->tax_rate ? number_format($item->tax_rate, 2) : 0; ?>%)</td>
                            <td><?= $this->sma->formatMoney($item->discount) ?></td>
                            <td><?= $this->sma->formatMoney($item->total) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    function products_combo_items() {
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
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Products_Combo_Items')));
        $meta = array('page_title' => lang('Products_Combo_Items'), 'bc' => $bc);
        $this->page_construct('reports/products_combo_items', $meta, $this->data);
    }

    function get_products_combo_items_report() {
        $segment = '';
        if ($this->uri->segment(3))
            $segment = $this->uri->segment(3);
        //echo $FileType = $this->uri->segment(3); exit;
        if ($segment != '') {

            // $this->db->select("combo_item_name, combo_item_code, sum(combo_item_stock) as combo_item_stock, sum(combo_item_qty) as combo_item_qty, sum(combo_item_unit_price) as combo_item_unit_price, sum(combo_product_sale_quantity) as combo_product_sale_quantity, sum(combo_items_sale_quantity) as combo_items_sale_quantity, sum(combo_items_sale_quantity_cost) as combo_items_sale_quantity_cost ")
            // ->from('view_combo_products_items_sale')
            // ->group_by('combo_item_code'); 

            $this->db->select("`combo_item_name`, `combo_item_code`, sum(CASE WHEN item_purchase_qty IS NOT NULL THEN item_purchase_qty ELSE 0 END) as purchased_qty, sum(CASE WHEN combo_items_purchase_quantity_cost IS NOT NULL THEN combo_items_purchase_quantity_cost ELSE 0 END) as purchased_cost, sum(combo_items_sale_quantity) as sold_qty, sum(CASE WHEN combo_items_sale_quantity_cost IS NOT NULL THEN combo_items_sale_quantity_cost ELSE 0 END) as sold_cost, (sum(CASE WHEN combo_items_sale_quantity_cost IS NOT NULL THEN combo_items_sale_quantity_cost ELSE 0 END) - sum(CASE WHEN combo_items_purchase_quantity_cost IS NOT NULL THEN combo_items_purchase_quantity_cost ELSE 0 END)) as Profit, sum(CASE WHEN combo_item_stock IS NOT NULL THEN combo_item_stock ELSE 0 END) as stock_qty, sum(CASE WHEN total_combo_items_cost IS NOT NULL THEN total_combo_items_cost ELSE 0 END) as stock_cost ")
                    ->from('view_combo_products_items_sale')
                    ->group_by('combo_item_code');

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
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Combo Item Report');
                $this->excel->getActiveSheet()->setTitle(lang('products_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('combo_item_name'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('combo_item_code'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('purchased_qty'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('purchased_cost'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('sold_qty'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('sold_cost'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Profit'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('stock_qty'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('stock_cost'));

                $row = 3;
                $pcost = 0;
                $pQty = 0;
                $sQty = 0;
                $scost = 0;
                $prAmt = 0;
                $stqty = 0;
                $ststock = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->combo_item_name);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->combo_item_code);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->purchased_qty);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->purchased_cost);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->sold_qty);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->sold_cost);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->Profit);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->stock_qty);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->stock_cost);

                    $pQty += $data_row->purchased_qty;
                    $pcost += $data_row->purchased_cost;
                    $sQty += $data_row->sold_qty;
                    $scost += $data_row->sold_cost;
                    $prAmt += $data_row->Profit;
                    $stqty += $data_row->stock_qty;
                    $ststock += $data_row->stock_cost;
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("C" . $row . ":H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $pQty);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $pcost);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $sQty);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $scost);
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $prAmt);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $stqty);
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $ststock);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(25);

                $filename = 'products_combo_item_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($segment == 'pdf') {
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
                if ($segment == 'xls') {
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
                if ($segment == 'img') {
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_combo_item_report.pdf', __FILE__));
                    redirect("reports/create_image/products_combo_item_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            //CASE WHEN combo_items_purchase_quantity_cost IS NOT NULL THEN combo_items_purchase_quantity_cost ELSE 0 END combo_items_sale_quantity_cost
            $this->datatables->select("combo_item_name, combo_item_code, CONCAT(sum(CASE WHEN item_purchase_qty IS NOT NULL THEN item_purchase_qty ELSE 0 END) , '__', sum(CASE WHEN combo_items_purchase_quantity_cost IS NOT NULL THEN combo_items_purchase_quantity_cost ELSE 0 END) ) as purchased, CONCAT(sum(combo_items_sale_quantity) , '__', sum(CASE WHEN combo_items_sale_quantity_cost IS NOT NULL THEN combo_items_sale_quantity_cost ELSE 0 END) ) as sold, (sum(CASE WHEN combo_items_sale_quantity_cost IS NOT NULL THEN combo_items_sale_quantity_cost ELSE 0 END) - sum(CASE WHEN combo_items_purchase_quantity_cost IS NOT NULL THEN combo_items_purchase_quantity_cost ELSE 0 END)) as Profit, CONCAT(sum(CASE WHEN combo_item_stock IS NOT NULL THEN combo_item_stock ELSE 0 END) , '__', sum(CASE WHEN total_combo_items_cost IS NOT NULL THEN total_combo_items_cost ELSE 0 END) ) as stock ")
                    ->from('view_combo_products_items_sale')
                    ->group_by('combo_item_code');
            echo $this->datatables->generate();

            //CASE WHEN combo_items_sale_quantity_cost IS NOT NULL THEN combo_items_sale_quantity_cost ELSE 0 END
        }
    }

    /* Daily Purchase Item list */

    public function daily_purchase_items() {
        $date = $_GET['date'];
        if (empty($date))
            return FALSE;
        $purchase_data = $this->reports_model->getDailyPurchaseItems($date);
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
                    <?php foreach ($purchase_data as $key => $item) { ?>
                        <tr>
                            <td><?= ++$i ?></td>
                            <td><?= $item->product_name ?></td>
                            <td><?= $item->product_code ?></td>
                            <td><?= $this->sma->formatMoney($item->net_unit_cost) ?></td>
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

    public function daily_purchase_items_print() {
        $date = $_GET['date'];
        if (empty($date))
            return FALSE;
        $purchase_data = $this->reports_model->getDailyPurchaseItems($date);
        ?>
        <div class="table-responsive">
            <font size="4px" face="Times New Roman" >
            <table class="table table-bordered" >
                <thead>
                    <tr id="tr_data">
                        <th>#</th>
                        <th  style="width:25px;">Product Name</th>
                       <!--  <th>Code</th> -->
                        <th>Price</th>
                        <th  >Qty</th>
                       <!--  <th>Units</th> -->
                      <!--   <th>Tax Rate</th> -->
                        <th>Tax</th>
                        <th>Disc</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchase_data as $key => $item) { ?>
                        <tr id="tr_get">
                            <td><?= ++$i ?></td>
                            <td style="width:25px;"><?= $item->product_name ?></td>
                        <!--     <td><?= $item->product_code ?></td> -->
                            <td><?= $this->sma->formatMoney($item->net_unit_cost) ?></td>
                            <td><?= number_format($item->qty, 2) ?></td>
                            <!--  <td><?= $item->unit ?></td> -->
                            <!--   <td><?= $item->tax_rate ? number_format($item->tax_rate, 2) : 0; ?>%</td> -->
                            <td><?= $this->sma->formatMoney($item->tax) ?><br>(<?= $item->tax_rate ? number_format($item->tax_rate, 2) : 0; ?>%)</td>
                            <td><?= $this->sma->formatMoney($item->discount) ?></td>
                            <td><?= $this->sma->formatMoney($item->total) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php
    }

    function product_varient_report() {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->data['brands'] = $this->site->getAllBrands();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['max_varient_count'] = $this->reports_model->max_varient_count();
        $this->data['simple_datatable'] = 'simple_datatable';
        $Data['category'] = $this->input->get('category') ? $this->input->get('category') : NULL;
        $Data['brand'] = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $Data['warehouse'] = $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Product_Varient_Stock_Report')));
        $meta = array('page_title' => lang('Product_Varient_Stock_Report'), 'bc' => $bc);
        $this->page_construct('reports/product_varient_report', $meta, $this->data);
    }

    function load_product_varient_report() {
        $Data['category'] = $this->input->get('category') ? $this->input->get('category') : NULL;
        $Data['brand'] = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $Data['warehouse'] = $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $Data['v'] = $this->input->get('v') ? $this->input->get('v') : NULL;
        //$Query = "select p.name, p.code, c.name as cat_name, b.name as brand_name, p.quantity as qty, (p.quantity * p.cost) as product_cost from sma_products p inner join sma_categories c on p.category_id=c.id inner join sma_brands b on b.id=p.brand inner join sma_warehouses_products_variants wpv on p.id=wpv.product_id group by wpv.product_id ";

        $total = $this->reports_model->count_product_varient_data($Data, isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
        $data = array();
        $Result = $this->reports_model->load_product_varient_data($Data, isset($_REQUEST['start']) ? $_REQUEST['start'] : "", isset($_REQUEST['length']) ? $_REQUEST['length'] : "", isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
        $dataArray = array();
        if ($Result->num_rows() > 0) {
            $Res = $Result->result_array();
            foreach ($Res as $key => $value) {
                $ProductId = $value['product_id'];
                if ($warehouse) {
                    $Sql = "select pv.product_id, p.name as p_name, ((select quantity as warehouse_qty from sma_warehouses_products_variants swpv where swpv.option_id=pv.id  and swpv.product_id='$ProductId' and swpv.warehouse_id='$warehouse' ) * (p.cost+pv.price)) as varient_cost, pv.name, pv.cost, pv.price, pv.quantity, (select quantity as warehouse_qty from sma_warehouses_products_variants swpv where swpv.option_id=pv.id  and swpv.product_id='$ProductId' and swpv.warehouse_id='$warehouse' ) as warehouse_qty from sma_products p inner join sma_product_variants pv on p.id=pv.product_id where p.id='$ProductId' ";
                } else {
                    $Sql = "select pv.product_id, p.name as p_name, (pv.quantity * (p.cost+pv.price)) as varient_cost, pv.name, pv.cost, pv.price, pv.quantity from sma_products p inner join sma_product_variants pv on p.id=pv.product_id where p.id='$ProductId' ";
                }
                $Res = $this->db->query($Sql);
                $Row = $Res->result_array();
                $row_data['product_id'] = $value['product_id'];
                $row_data['name'] = $value['name'];
                $row_data['code'] = $value['code'];
                $row_data['cat_name'] = $value['cat_name'];
                $row_data['brand_name'] = $value['brand_name'];
                if ($warehouse) {
                    $row_data['qty_product_cost'] = $this->sma->formatQuantity($value['wh_qty']) . ' (' . $this->sma->formatMoney($value['product_cost']) . ')';
                } else {
                    $row_data['qty_product_cost'] = $this->sma->formatQuantity($value['qty']) . ' (' . $this->sma->formatMoney($value['product_cost']) . ')';
                }

                $i = 1;
                $max_varient_count = $this->reports_model->max_varient_count();
                //echo '<pre>';
                if (!empty($Row)) {

                    foreach ($Row as $varient_key => $varient_value) {
                        $row_data['V' . $i] = $varient_value['name'];
                        if ($warehouse)
                            $row_data['v_qty_' . $i] = $this->sma->formatQuantity($varient_value['warehouse_qty']) . ' (' . $this->sma->formatMoney($varient_value['varient_cost']) . ')';
                        else
                            $row_data['v_qty_' . $i] = $this->sma->formatQuantity($varient_value['quantity']) . ' (' . $this->sma->formatMoney($varient_value['varient_cost']) . ')';
                        $row_data['v_details_' . $i] = $row_data['V' . $i] . '<br/>' . $row_data['v_qty_' . $i];
                        $i++;
                    }

                    $inr = $i;
                    //echo $value['name'].' '.$i.'<>';
                    for ($i; $i <= $max_varient_count; $i++) {

                        $row_data['V' . $i] = '';
                        $row_data['v_qty_' . $i] = '';
                        $row_data['v_details_' . $i] = '';
                    }
                } else {
                    for ($i; $i <= $max_varient_count; $i++) {
                        $row_data['V' . $i] = '';
                        $row_data['v_qty_' . $i] = '';
                        $row_data['v_details_' . $i] = '';
                    }
                }

                $dataArray[] = $row_data;
            }
        }
        if ($Data['v'] == 'export') {

            $segment = '';
            if ($this->uri->segment(3))
                $segment = $this->uri->segment(3);
            //exit;
            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

            $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
            $this->excel->getActiveSheet()->mergeCells('A1:I1');
            $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Varient Item Report');
            $this->excel->getActiveSheet()->setTitle(lang('products_report'));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('Product Name'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Code'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Category'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Brand'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Qty/(Cost*Qty)'));
            $ArraySequence = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
            $SequenceInr = 5;
            for ($i = 1; $i <= $max_varient_count; $i++) {
                $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . '2', 'Varient');
                $SequenceInr++;
                $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . '2', lang(' Qty/(Cost*Qty)'));
                $SequenceInr++;
            }
            $row = 3;
            $pcost = 0;
            $pQty = 0;
            $sQty = 0;
            $scost = 0;
            $prAmt = 0;
            $stqty = 0;
            $ststock = 0;
            foreach ($dataArray as $keys => $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row['name']);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row['code']);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row['cat_name']);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row['brand_name']);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row['qty_product_cost']);
                $SequenceInr = 5;
                for ($i = 1; $i <= $max_varient_count; $i++) {
                    $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . $row, $data_row['V' . $i]);
                    $SequenceInr++;
                    $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . $row, $data_row['v_qty_' . $i]);
                    $SequenceInr++;
                }

                /* $pQty += $data_row->purchased_qty;
                  $pcost += $data_row->purchased_cost;
                  $sQty += $data_row->sold_qty;
                  $scost += $data_row->sold_cost;
                  $prAmt += $data_row->Profit;
                  $stqty += $data_row->stock_qty;
                  $ststock += $data_row->stock_cost; */
                $row++;
            }
            $this->excel->getActiveSheet()->getStyle("C" . $row . ":H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
            /* $this->excel->getActiveSheet()->SetCellValue('C' . $row, $pQty);
              $this->excel->getActiveSheet()->SetCellValue('D' . $row, $pcost);
              $this->excel->getActiveSheet()->SetCellValue('E' . $row, $sQty);
              $this->excel->getActiveSheet()->SetCellValue('F' . $row, $scost);
              $this->excel->getActiveSheet()->SetCellValue('G' . $row, $prAmt);
              $this->excel->getActiveSheet()->SetCellValue('H' . $row, $stqty);
              $this->excel->getActiveSheet()->SetCellValue('I' . $row, $ststock);

              $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
              $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
              $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
              $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
              $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
              $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
              $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
              $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
              $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(25); */

            $filename = 'products_varient_item_report';
            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            if ($segment == 'pdf') {
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
            if ($segment == 'xls') {
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
            if ($segment == 'img') {
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
                $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_varient_item_report.pdf', __FILE__));
                redirect("reports/create_image/products_varient_item_report.pdf");
                exit();
            }
        } else {
            $DataArray = array('draw' => $_REQUEST['draw'], 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $dataArray);
            echo json_encode($DataArray);
        }
    }

    function product_varient_sale_report() {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->data['brands'] = $this->site->getAllBrands();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['max_varient_count'] = $this->reports_model->max_varient_count('Variant');
        $this->data['varient_name'] = $this->reports_model->getVarientName('Variant');

        $Data['category'] = $this->input->get('category') ? $this->input->get('category') : NULL;
        $Data['brand'] = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $Data['warehouse'] = $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $Data['Type'] = 'Variant';
        $this->data['limit_product_varient'] = $this->reports_model->count_product_varient_sale_data($Data);

        $this->data['simple_datatable'] = 'simple_datatable';
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Products_Varient_Sale_Report')));
        $meta = array('page_title' => lang('Products_Varient_Sale_Report'), 'bc' => $bc);
        $this->page_construct('reports/product_varient_sale_report', $meta, $this->data);
    }

    function load_product_varient_sale_report() {
        $Data['Type'] = 'Variant';
        $Data['category'] = $this->input->get('category') ? $this->input->get('category') : NULL;
        $Data['brand'] = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $Data['warehouse'] = $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $Data['v'] = $this->input->get('v') ? $this->input->get('v') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        if ($start_date) {
            $Data['start_date'] = $start_date = $this->sma->fld($start_date);
            $Data['end_date'] = $end_date = $this->sma->fld($end_date);
        }
        if ($Data['v'] == 'export') {
            $strtlimit = $this->input->get('strtlimit') ? $this->input->get('strtlimit') : NULL;
            $limt = explode("-", $strtlimit);
            if ($limt) {
                $Data['start'] = $start = $limt[0];
                $Data['limit'] = $limit = $limt[1];
            }
            //echo $start.' '.$limit; exit;
        }

        //$Query = "select p.name, p.code, c.name as cat_name, b.name as brand_name, p.quantity as qty, (p.quantity * p.cost) as product_cost from sma_products p inner join sma_categories c on p.category_id=c.id inner join sma_brands b on b.id=p.brand inner join sma_warehouses_products_variants wpv on p.id=wpv.product_id group by wpv.product_id ";

        $total = $this->reports_model->count_product_varient_sale_data($Data, isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
        $data = array();
        $Result = $this->reports_model->load_product_varient_sale_data($Data, isset($_REQUEST['start']) ? $_REQUEST['start'] : "", isset($_REQUEST['length']) ? $_REQUEST['length'] : "", isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
        $dataArray = array();
        if ($Result->num_rows() > 0) {
            $Res = $Result->result_array();
            foreach ($Res as $key => $value) {
                $ProductId = $value['product_id'];


                $row_data['product_id'] = $value['product_id'];
                $row_data['name'] = $value['name'];
                $row_data['code'] = $value['code'];
                $row_data['cat_name'] = $value['cat_name'];
                $row_data['brand_name'] = $value['brand_name'];


                $i = 1;
                $VarientName = $this->reports_model->getVarientName('Variant');
                $TotalQty = 0;
                if (!empty($VarientName)) {
                    foreach ($VarientName as $key_varient_name => $value_varient_name) {
                        $Sql = "select pv.product_id, p.name as p_name, sum(ssi.subtotal) as varient_cost, pv.name, pv.cost, pv.price, sum(ssi.quantity) as quantity from sma_products p inner join sma_product_variants pv on p.id=pv.product_id inner join sma_sale_items ssi on pv.id=ssi.option_id inner join sma_sales s on s.id=ssi.sale_id where p.id='$ProductId' and pv.name='" . $value_varient_name['name'] . "'";

                        if ($warehouse) {
                            $Sql .= " and ssi.warehouse_id='$warehouse' ";
                        }
                        if ($Data['start_date']) {
                            $Sql .= " and DATE(s.date) BETWEEN '" . $Data['start_date'] . "' and '" . $Data['end_date'] . "'";
                        }
                        $Sql .= " group by ssi.option_id ";
                        $Res = $this->db->query($Sql);
                        $Row = $Res->result_array();
                        if (!empty($Row)) {
                            foreach ($Row as $varient_key => $varient_value) {
                                $row_data['v_' . $value_varient_name['id']] = $this->sma->formatQuantity($varient_value['quantity']);
                                $TotalQty += $varient_value['quantity'];
                            }
                        } else {
                            $row_data['v_' . $value_varient_name['id']] = '';
                        }
                    }
                }
                $row_data['qty_product_cost'] = $this->sma->formatQuantity($TotalQty);
                $dataArray[] = $row_data;
            }
        }
        if ($Data['v'] == 'export') {
            $segment = '';
            if ($this->uri->segment(3))
                $segment = $this->uri->segment(3);
            //exit;
            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

            $this->excel->getActiveSheet()->getStyle("A1:AZ1")->applyFromArray($style);
            $this->excel->getActiveSheet()->mergeCells('A1:AZ1');
            $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Varient Item Report');
            $this->excel->getActiveSheet()->setTitle(lang('products_report'));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('Product Name'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Code'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Category'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Brand'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Qty'));
            $VariantLength = count($VarientName);
            $SequenceLength = $VariantLength + 5;
            $Sequence = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            $ArraySequence = array();
            $length = 1;
            $len = 0;
            $len_inr = 0;
            for ($i = 0; $i <= $SequenceLength - 1; $i++) {
                if ($i > 25)
                    $ArraySequence[] = $Sequence[$len] . $Sequence[$len_inr];
                else
                    $ArraySequence[] = $Sequence[$i];

                //echo '<br>'.$length.' '.$i.' '.$len;
                $length++;
                $len_inr++;
                if ($length == 27) {
                    $length = 1;
                    $len_inr = 0;
                    if ($i > 25) {
                        $len++;
                    }
                }
            }
            $SequenceInr = 5;
            if (!empty($VarientName)) {
                foreach ($VarientName as $key_varient_name => $value_varient_name) {
                    $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . '2', $value_varient_name['name']);
                    $SequenceInr++;
                }
            }
            $row = 3;
            $pcost = 0;
            $pQty = 0;
            $sQty = 0;
            $scost = 0;
            $prAmt = 0;
            $stqty = 0;
            $ststock = 0;
            foreach ($dataArray as $keys => $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row['name']);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row['code']);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row['cat_name']);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row['brand_name']);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row['qty_product_cost']);
                $SequenceInr = 5;
                if (!empty($VarientName)) {
                    foreach ($VarientName as $key_varient_name => $value_varient_name) {
                        $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . $row, $data_row['v_' . $value_varient_name['id']]);
                        $SequenceInr++;
                    }
                }
                $row++;
            }
            //$this->excel->getActiveSheet()->getStyle("C" . $row . ":H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
            $filename = 'sale_products_varient_item_report';
            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            if ($segment == 'pdf') {
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
            if ($segment == 'xls') {
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
            if ($segment == 'img') {
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
                $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/sale_products_varient_item_report.pdf', __FILE__));
                redirect("reports/create_image/sale_products_varient_item_report.pdf");
                exit();
            }
        } else {
            $DataArray = array('draw' => $_REQUEST['draw'], 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $dataArray);
            echo json_encode($DataArray);
        }
    }

    function product_varient_stock_report() {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->data['brands'] = $this->site->getAllBrands();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['varient_name'] = $this->reports_model->getVarientName('Variant');
        $Data['Type'] = 'Variant';
        $this->data['limit_product_varient'] = $this->reports_model->count_product_varient_data($Data);
        //print_r($this->data['varient_name']);
        //exit;
        $this->data['simple_datatable'] = 'simple_datatable';
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Product_Varient_Stock_Report')));
        $meta = array('page_title' => lang('Product_Varient_Stock_Report'), 'bc' => $bc);
        $this->page_construct('reports/product_varient_stock_report', $meta, $this->data);
    }

    function load_product_varient_stock_report() {
        $Data['Type'] = 'Variant';
        $Data['category'] = $this->input->get('category') ? $this->input->get('category') : NULL;
        $Data['brand'] = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $Data['warehouse'] = $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $Data['v'] = $this->input->get('v') ? $this->input->get('v') : NULL;
        if ($Data['v'] == 'export') {
            $strtlimit = $this->input->get('strtlimit') ? $this->input->get('strtlimit') : NULL;
            $limt = explode("-", $strtlimit);
            if ($limt) {
                $Data['start'] = $start = $limt[0];
                $Data['limit'] = $limit = $limt[1];
            }
            //echo $start.' '.$limit; exit;
        }
        $total = $this->reports_model->count_product_varient_data($Data, isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
        $data = array();
        $Result = $this->reports_model->load_product_varient_data($Data, isset($_REQUEST['start']) ? $_REQUEST['start'] : "", isset($_REQUEST['length']) ? $_REQUEST['length'] : "", isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
        $dataArray = array();
        if ($Result->num_rows() > 0) {
            $Res = $Result->result_array();
            foreach ($Res as $key => $value) {
                $ProductId = $value['product_id'];

                $row_data['product_id'] = $value['product_id'];
                $row_data['name'] = $value['name'];
                $row_data['code'] = $value['code'];
                $row_data['cat_name'] = $value['cat_name'];
                $row_data['brand_name'] = $value['brand_name'];
                if ($warehouse) {
                    $row_data['qty_product_cost'] = $this->sma->formatQuantity($value['wh_qty']);
                } else {
                    $row_data['qty_product_cost'] = $this->sma->formatQuantity($value['qty']);
                }

                $i = 1;
                $VarientName = $this->reports_model->getVarientName('Variant');
                //echo '<pre>';
                //print_r($VarientName);
                if (!empty($VarientName)) {
                    foreach ($VarientName as $key_varient_name => $value_varient_name) {
                        if ($warehouse) {
                            $Sql = "select swpv.quantity from sma_product_variants spv inner join sma_warehouses_products_variants swpv on spv.id=swpv.option_id  where swpv.product_id='$ProductId' and swpv.warehouse_id='$warehouse' and spv.name='" . $value_varient_name['name'] . "'";
                        } else {
                            $Sql = "select pv.quantity  from sma_product_variants pv where pv.product_id='$ProductId' and pv.name='" . $value_varient_name['name'] . "'";
                        }
                        $Res = $this->db->query($Sql);
                        $Row = $Res->result_array();
                        if (!empty($Row)) {
                            foreach ($Row as $varient_key => $varient_value) {
                                $row_data['v_' . $value_varient_name['id']] = $this->sma->formatQuantity($varient_value['quantity']);
                            }
                        } else {
                            $row_data['v_' . $value_varient_name['id']] = '';
                        }
                    }
                }

                $dataArray[] = $row_data;
            }
        }
        //echo '<pre>';
        //print_r($dataArray);
        //exit;
        if ($Data['v'] == 'export') {

            $segment = '';
            if ($this->uri->segment(3))
                $segment = $this->uri->segment(3);
            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

            $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
            $this->excel->getActiveSheet()->mergeCells('A1:I1');
            $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Varient Item Report');
            $this->excel->getActiveSheet()->setTitle(lang('products_report'));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('Product Name'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Code'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Category'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Brand'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Qty/(Cost*Qty)'));
            $VariantLength = count($VarientName);
            $SequenceLength = $VariantLength + 5;
            $Sequence = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            $ArraySequence = array();
            $length = 1;
            $len = 0;
            $len_inr = 0;
            for ($i = 0; $i <= $SequenceLength - 1; $i++) {
                if ($i > 25)
                    $ArraySequence[] = $Sequence[$len] . $Sequence[$len_inr];
                else
                    $ArraySequence[] = $Sequence[$i];

                //echo '<br>'.$length.' '.$i.' '.$len;
                $length++;
                $len_inr++;
                if ($length == 27) {
                    $length = 1;
                    $len_inr = 0;
                    if ($i > 25) {
                        $len++;
                    }
                }
            }
            //$ArraySequence = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
            $SequenceInr = 5;
            if (!empty($VarientName)) {
                foreach ($VarientName as $key_varient_name => $value_varient_name) {
                    $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . '2', $value_varient_name['name']);
                    $SequenceInr++;
                }
            }
            $row = 3;
            $pcost = 0;
            $pQty = 0;
            $sQty = 0;
            $scost = 0;
            $prAmt = 0;
            $stqty = 0;
            $ststock = 0;
            foreach ($dataArray as $keys => $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row['name']);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row['code']);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row['cat_name']);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row['brand_name']);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row['qty_product_cost']);
                $SequenceInr = 5;
                if (!empty($VarientName)) {
                    foreach ($VarientName as $key_varient_name => $value_varient_name) {
                        $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . $row, $data_row['v_' . $value_varient_name['id']]);
                        $SequenceInr++;
                    }
                }

                $row++;
            }
            $this->excel->getActiveSheet()->getStyle("C" . $row . ":H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

            $filename = 'products_varient_item_report';
            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            if ($segment == 'pdf') {
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
            if ($segment == 'xls') {
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
            if ($segment == 'img') {
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
                $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_varient_item_report.pdf', __FILE__));
                redirect("reports/create_image/products_varient_item_report.pdf");
                exit();
            }
        } else {
            $DataArray = array('draw' => $_REQUEST['draw'], 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $dataArray);
            echo json_encode($DataArray);
        }
    }

    /* purchase_product_tax 23-11-2019 */

    public function daily_purchase_items_taxes() {
        $date = $_GET['date'];
        // $warehouse_id = $_GET['active_warehouse_id'];
        if (empty($date))
            return FALSE;

        $purchasetax_data = $this->reports_model->getDailyPurchaseItemsTaxes($date);
        if ($purchasetax_data) {
            foreach ($purchasetax_data as $tax) {
                $gst[$tax->gst_rate]['cgst'] += $tax->cgst;
                $gst[$tax->gst_rate]['sgst'] += $tax->sgst;
                $gst[$tax->gst_rate]['igst'] += $tax->igst;
            }
        }
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
                    foreach ($gst as $tax_rate => $item) {
                        $totalTax += ($item['cgst'] + $item['sgst'] + $item['igst']);
                        $gsi_tax = $this->reports_model->getpurchasetaxitemid($item->item_id);
                        $igst_taxrate = $item['igst'] ? $tax_rate : 0;
                        $gst_taxrate = $item['igst'] ? 0 : $tax_rate;
                        $raxrate = $igst_taxrate + $gst_taxrate + $gst_taxrate;
                        $taxamount = $item['cgst'] + $item['sgst'] + $item['igst'];
                        ?>
                        <tr>
                            <td class="text-center">GST <?= $raxrate ?>%</td>                      
                            <td class="text-center"><?= ($gst_taxrate) ?>%</td>
                            <td class="text-center"><?= ($gst_taxrate) ?>%</td>
                            <td class="text-center"><?= ($igst_taxrate) ?>%</td>             
                            <td class="text-center">Rs. <?= number_format($taxamount, 4); ?></td>
                        </tr>
                        <?php
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

    public function monthly_purchase_items_taxes() {
        $month = $_GET['month'];
        $year = $_GET['year'];

        if (empty($month) || empty($year))
            return FALSE;

        $purchasetax_data = $this->reports_model->getMonthPurchaseItemsTaxes($month, $year);

        if ($purchasetax_data) {
            foreach ($purchasetax_data as $tax) {
                $gst[$tax->gst_rate]['cgst'] += $tax->cgst;
                $gst[$tax->gst_rate]['sgst'] += $tax->sgst;
                $gst[$tax->gst_rate]['igst'] += $tax->igst;
            }
        }
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
                    foreach ($gst as $tax_rate => $item) {
                        $totalTax += ($item['cgst'] + $item['sgst'] + $item['igst']);
                        $gsi_tax = $this->reports_model->getpurchasetaxitemid($item->item_id);
                        $igst_taxrate = $item['igst'] ? $tax_rate : 0;
                        $gst_taxrate = $item['igst'] ? 0 : $tax_rate;
                        $raxrate = $igst_taxrate + $gst_taxrate + $gst_taxrate;
                        $taxamount = $item['cgst'] + $item['sgst'] + $item['igst'];
                        ?>
                        <tr>
                            <td class="text-center">GST <?= $raxrate ?>%</td>                      
                            <td class="text-center"><?= ($gst_taxrate) ?>%</td>
                            <td class="text-center"><?= ($gst_taxrate) ?>%</td>
                            <td class="text-center"><?= ($igst_taxrate) ?>%</td>             
                            <td class="text-center">Rs. <?= number_format($taxamount, 4); ?></td>
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

    /*     * HSN Code* */

    public function hsncode_reports() {
        $this->sma->checkPermissions('hsncode_reports');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Hsn Code Tax Reports')));
        $meta = array('page_title' => lang('Hsn Code Tax Reports'), 'bc' => $bc);
        $this->page_construct('reports/hsncode_reports', $meta, $this->data);
    }

    public function getTaxHsnCodeReports($pdf = NULL, $xls = NULL) {

        $this->sma->checkPermissions('hsnCodeTaxs', TRUE);

        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;


        if ($pdf || $xls) {

            $GSTRate = $this->reports_model->salesHsnCodeReports($start_date, $end_date);
            if (!empty($GSTRate)) {
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:H1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:H1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Hsn Code Tax Report');
                $this->excel->getActiveSheet()->setTitle(lang('Hsn Code Tax Report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('HSN Code'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('GST Rate'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Basic Amt.'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('CGST'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('SGST'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('IGST'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Total GST'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('Sales Amt'));

                $row = 3;
                $totalBasicAmt = 0;
                $totalcgst = 0;
                $totalsgst = 0;
                $totaligst = 0;
                $totalgst = 0;
                $totalsales = 0;

                foreach ($GSTRate as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->hsn_code);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->tax_rate);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->basic_amount);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->cgst);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->sgst);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->igst);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->total_gst);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->total_sales);

                    $totalBasicAmt += $data_row->basic_amount;
                    $totalcgst += $data_row->cgst;
                    $totalsgst += $data_row->sgst;
                    $totaligst += $data_row->igst;
                    $totalgst += $data_row->total_gst;
                    $totalsales += $data_row->total_sales;

                    $row++;
                }

                //$this->excel->getActiveSheet()->getStyle("C" . $row . ":H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $totalBasicAmt);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $totalcgst);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $totalsgst);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $totaligst);
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $totalgst);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $totalsales);
                $filename = 'Hsn_Code_Tax_Report';
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
            $this->datatables->select('(sma_sale_items.hsn_code) as hsn_code,'
                    . 'ROUND(sma_sale_items.tax,2) as tax_rate,'
                    . ' SUM(sma_sale_items.invoice_unit_price * sma_sale_items.quantity) as basic_amount ,'
                    . ' SUM(sma_sale_items.cgst) as cgst,sum(sma_sale_items.sgst) as sgst,'
                    . ' sum(sma_sale_items.igst) as igst, sum(sma_sale_items.sgst + sma_sale_items.cgst + sma_sale_items.igst) as total_gst , '
                    . ' sum(sma_sale_items.invoice_total_net_unit_price) as total_sales');
            $this->datatables->from('sma_sale_items');
            $this->datatables->where('sma_sale_items.hsn_code != " "');


            if (isset($start_date) && isset($end_date)) {
                $this->datatables->join('sma_sales', 'sma_sales.id = sma_sale_items.sale_id');
                //$this->db->where('sma_sales.date >=', $start_date);
                //$this->db->where('sma_sales.date <=', $end_date);
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            $this->datatables->group_by(['sma_sale_items.hsn_code', 'sma_sale_items.tax']);
            echo $this->datatables->generate();
        }
    }

    /**
     * End HSN Code Tax Reports
     */
    /*     * *
     * Tax Reports
     */
    public function taxreports() {



        $this->sma->checkPermissions('taxreports');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Tax Reports')));
        $meta = array('page_title' => lang('Tax Reports'), 'bc' => $bc);
        $this->page_construct('reports/taxreports', $meta, $this->data);
    }

    /**
     * 
     * @param type $pdf
     * @param type $xls
     */
    public function getTaxReports($pdf = NULL, $xls = NULL) {

        $this->sma->checkPermissions('taxreports', TRUE);

        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;


        if ($pdf || $xls) {

            $GSTRate = $this->reports_model->salesGSTRateReports($start_date, $end_date);

            if (!empty($GSTRate)) {
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:H1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:H1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Tax Report');


                $this->excel->getActiveSheet()->setTitle(lang('Tax Report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Sr. No.'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('GST Rate'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Taxable Amt.'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('SGST'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('CGST'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('IGST'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Total GST'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('Sales Amt.'));


                $row = 3;
                $totalbasic = $totalSGST = $totalCGST = $totalIGST = $totalGST = $totalsales = 0;

                foreach ($GSTRate as $key => $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $key + 1);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->tax_rate . '%');
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->basic_amount);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->sgst);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->cgst);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->igst);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->total_gst);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->total_sales);

                    $totalbasic += $data_row->basic_amount;
                    $totalSGST += $data_row->sgst;
                    $totalCGST += $data_row->cgst;
                    $totalIGST += $data_row->igst;
                    $totalGST += $data_row->total_gst;
                    $totalsales += $data_row->total_sales;

                    $row++;
                }


                $this->excel->getActiveSheet()->SetCellValue("A" . $row, "");
                $this->excel->getActiveSheet()->SetCellValue("B" . $row, "Total");
                $this->excel->getActiveSheet()->SetCellValue("C" . $row, $totalbasic);
                $this->excel->getActiveSheet()->SetCellValue("D" . $row, $totalSGST);
                $this->excel->getActiveSheet()->SetCellValue("E" . $row, $totalCGST);
                $this->excel->getActiveSheet()->SetCellValue("F" . $row, $totalIGST);
                $this->excel->getActiveSheet()->SetCellValue("G" . $row, $totalGST);
                $this->excel->getActiveSheet()->SetCellValue("H" . $row, $totalsales);



                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(20);


                $filename = 'Tax Report';
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
            $this->datatables->select('ROUND(sma_sale_items.tax,2) as tax_rate,'
                    . ' sum(sma_sale_items.invoice_unit_price * sma_sale_items.quantity) as basic_amount ,'
                    . ' SUM(sma_sale_items.sgst) as sgst,sum(sma_sale_items.cgst) as cgst,'
                    . ' sum(sma_sale_items.igst) as igst, sum(sma_sale_items.sgst + sma_sale_items.cgst + sma_sale_items.igst) as total_gst , '
                    . 'sum(sma_sale_items.invoice_total_net_unit_price) as total_sales');
            $this->datatables->from('sma_sale_items');
            $this->datatables->where('sma_sale_items.gst_rate >  0');


            if (isset($start_date) && isset($end_date)) {
                $this->datatables->join('sma_sales', 'sma_sales.id = sma_sale_items.sale_id');
                //$this->db->where('sma_sales.date >=', $start_date);
                //$this->db->where('sma_sales.date <=', $end_date);
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            $this->datatables->group_by('sma_sale_items.tax');
            echo $this->datatables->generate();
        }
    }

    /**
     * End Tax Reports
     */
    /*     * new Sales Report* */
    public function sales_gst_reportnew() {
        $this->sma->checkPermissions('sales');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['salegstcount'] = $this->getCountSalesGst();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_New_report'), 'bc' => $bc);
        $this->page_construct('reports/sales_customer_reportnew', $meta, $this->data);
    }

    public function getSalesReportCnew($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('sales', TRUE);
        $SalesIds = '';
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $biller = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $reference_no = $this->input->get('reference_no') ? $this->input->get('reference_no') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $serial = $this->input->get('serial') ? $this->input->get('serial') : NULL;
        $gstn_opt = $this->input->get('gstn_opt') ? $this->input->get('gstn_opt') : NULL;
        $gstn_no = $this->input->get('gstn_no') ? $this->input->get('gstn_no') : NULL;
        $hsn_code = $this->input->get('hsn_code') ? $this->input->get('hsn_code') : NULL;
        $max_export_sales = $this->input->get('max_export_sales') ? $this->input->get('max_export_sales') : '0-200'; //0-500
        if (!empty($hsn_code)) {
            $SalesIds = $this->reports_model->getSaleIdByHsn($hsn_code);
        }
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }

        if ($pdf || $xls || $img) {
            list($start, $limit) = explode('-', $max_export_sales);
            $this->db->select("sales.id as sale_id,sales.date, sales.reference_no, sales.invoice_no,sales.biller, sales.customer,sales.product_tax as total_tax , 
                IF(comp.gstn_no IS NULL or comp.gstn_no = '', '-', comp.gstn_no) as gstn_no,   comp.address,  comp.city,  comp.phone,  comp.email , 
                sma_sales.grand_total as grand_total, sma_sales.paid as paid,sma_sales.rounding as rounding,sma_payments.paid_by, sales.payment_status", FALSE)
                    ->from('sales')
                    ->join('companies comp', 'sales.customer_id=comp.id', 'left')
                    ->join('sma_payments ', 'sales.id=sma_payments.sale_id', 'left')
                    ->limit($limit, $start)
                    ->group_by('sales.id')
                    ->order_by('sales.date desc');
            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->db->where('sales.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('sales.created_by', $user);
                    }
                }
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
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            if ($gstn_opt) {
                switch ($gstn_opt) {
                    case '-1':
                        $this->db->where("comp.gstn_no IS NULL OR comp.gstn_no = '' ");
                        break;
                    case '1':
                        $this->db->where("comp.gstn_no IS NOT NULL and comp.gstn_no != '' ");
                        break;
                    default:
                        break;
                }
            }
            if ($gstn_no) {
                $this->db->where("comp.gstn_no = '" . $gstn_no . "' ");
            }
            if ($SalesIds) {
                $this->db->where('sales.id in (' . $SalesIds . ')');
            }
            $this->db->group_by('sales.id');
            $q = $this->db->get();
            $data_sales = [];
            $saleCount = 0;
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    if (!in_array($row->sale_id, $data_sales)) {
                        $data_sales[] = $row->sale_id;
                    }
                    //Sales Details
                    $data[$row->sale_id]['sale_id'] = $row->sale_id;
                    $data[$row->sale_id]['date'] = $row->date;
                    $data[$row->sale_id]['reference_no'] = $row->reference_no;
                    $data[$row->sale_id]['invoice_no'] = $row->invoice_no;
                    $data[$row->sale_id]['biller'] = $row->biller;
                    $data[$row->sale_id]['customer'] = $row->customer;
                    $cantact = ($row->address) ? $row->address : '';
                    $cantact .= ($row->city) ? ' City:' . $row->city : '';
                    $cantact .= ($row->phone) ? ' Phone:' . $row->phone : '';
                    $cantact .= ($row->email) ? ' Email:' . $row->email : '';
                    $data[$row->sale_id]['address'] = $cantact;
                    $data[$row->sale_id]['gstn_no'] = $row->gstn_no;
                    $data[$row->sale_id]['grand_total'] = $row->grand_total + $row->rounding;
                    $data[$row->sale_id]['taxable_amt'] = $row->grand_total - $row->total_tax;
                    $data[$row->sale_id]['total_tax'] = $row->total_tax;
                    $data[$row->sale_id]['paid'] = $row->paid;
                    $data[$row->sale_id]['balance'] = $row->grand_total + $row->rounding - $row->paid;
                    $data[$row->sale_id]['paid_by'] = $row->paid_by;
                    $data[$row->sale_id]['payment_status'] = $row->payment_status;
                }//endforeach

                $uniqueSalesIds = array_unique($data_sales);
                //Get Sale items details
                $SalesItems = $this->reports_model->getSalesItmBySaleId($uniqueSalesIds, $product);
                // print_r($SalesItems);exit;
                if (is_array($SalesItems)) {
                    foreach ($SalesItems as $key => $SaleItemsRow) {
                        //Sales Items Details
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['items_id'] = $SaleItemsRow->items_id;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['code'] = $SaleItemsRow->product_code;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['name'] = $SaleItemsRow->product_name;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['variantname'] = $SaleItemsRow->variant_name;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['gst'] = ($SaleItemsRow->gst) ? substr($SaleItemsRow->gst, 0, -3) : 0;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['hsn_code'] = $SaleItemsRow->hsn_code;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['quantity'] = $SaleItemsRow->quantity;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['unit'] = $SaleItemsRow->unit;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['tax_amt'] = ($SaleItemsRow->item_tax) ? $SaleItemsRow->item_tax : 0;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['subtotal'] = $SaleItemsRow->subtotal;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['gst_rate'] = $SaleItemsRow->gst_rate;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['CGST'] = $SaleItemsRow->CGST;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['SGST'] = $SaleItemsRow->SGST;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['IGST'] = $SaleItemsRow->IGST;
                    }//end foreach
                }//end if
            } else {
                $data = NULL;
            }

            if (!empty($data)) {
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);

                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:AG1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:AG1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'GST Sales Report');

                $this->excel->getActiveSheet()->setTitle(lang('sales_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('sr no'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Invoice No'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('biller'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('customer'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('customer') . ' Contacts');
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('gstn'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Grand Total (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Taxable Amount (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('Tax Amount (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('Paid (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('M2', lang('Balance (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('N2', lang('Payment Method'));
                $this->excel->getActiveSheet()->SetCellValue('O2', lang('Payment Status'));

                //Sales Items Detail
                $this->excel->getActiveSheet()->SetCellValue('P2', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('Q2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('R2', lang('Varient'));
                $this->excel->getActiveSheet()->SetCellValue('S2', lang('hsn_code'));
                $this->excel->getActiveSheet()->SetCellValue('T2', lang('GST Rate (%)'));
                $this->excel->getActiveSheet()->SetCellValue('U2', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('V2', lang('unit'));
                $this->excel->getActiveSheet()->SetCellValue('W2', lang('CGST (%)'));
                $this->excel->getActiveSheet()->SetCellValue('X2', lang('CGST (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('Y2', lang('SGST (%)'));
                $this->excel->getActiveSheet()->SetCellValue('Z2', lang('SGST (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('AA2', lang('IGST (%)'));
                $this->excel->getActiveSheet()->SetCellValue('AB2', lang('IGST (Rs)'));
                $this->excel->getActiveSheet()->SetCellValue('AC2', lang('Subtotal (Rs)'));

                $row = 3;
                $cgst = 0;
                $sgst = 0;
                $igst = 0;
                $vat = 0;
                $cess = 0;
                $total = 0;
                $paid = 0;
                $balance = 0;
                $total_taxable_amt = 0;
                $totalSubtotal = 0;
                $sr = ($start) ? ($start - 1) : 0;

                $this->excel->getActiveSheet()->getStyle("A" . $row . ":AG" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                foreach ($data as $sale_id => $salesdata) {
                    $sale_data = (object) $salesdata;

                    $sr++;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, ($sr));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $this->sma->hrld($sale_data->date));
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $sale_data->invoice_no);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $sale_data->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $sale_data->biller);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $sale_data->customer);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $sale_data->address);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $sale_data->gstn_no);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $sale_data->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $sale_data->taxable_amt);
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $sale_data->total_tax);
                    $this->excel->getActiveSheet()->SetCellValue('L' . $row, $sale_data->paid);
                    $this->excel->getActiveSheet()->SetCellValue('M' . $row, $sale_data->balance);
                    $this->excel->getActiveSheet()->SetCellValue('N' . $row, $this->reports_model->getpaymentmode($sale_data->sale_id));
                    $this->excel->getActiveSheet()->SetCellValue('O' . $row, $sale_data->payment_status);

                    if (!empty($sale_data->items)) {
                        foreach ($sale_data->items as $saleitem_id => $salesItemsData) {

                            $sales_items_data = (object) $salesItemsData;
                            //print_r($sales_items_data);
                            $this->excel->getActiveSheet()->SetCellValue('P' . $row, $sales_items_data->code);
                            $this->excel->getActiveSheet()->SetCellValue('Q' . $row, $sales_items_data->name);
                            $this->excel->getActiveSheet()->SetCellValue('R' . $row, $sales_items_data->variantname);
                            $this->excel->getActiveSheet()->SetCellValue('S' . $row, $sales_items_data->hsn_code);
                            $this->excel->getActiveSheet()->SetCellValue('T' . $row, $sales_items_data->gst);
                            $this->excel->getActiveSheet()->SetCellValue('U' . $row, $sales_items_data->quantity);
                            $this->excel->getActiveSheet()->SetCellValue('V' . $row, lang($sales_items_data->unit));
                            $this->excel->getActiveSheet()->SetCellValue('W' . $row, $sales_items_data->IGST != '0.0000' ? 0 : $sales_items_data->gst_rate );
                            $this->excel->getActiveSheet()->SetCellValue('X' . $row, $sales_items_data->CGST);
                            $this->excel->getActiveSheet()->SetCellValue('Y' . $row, $sales_items_data->IGST != '0.0000' ? 0 : $sales_items_data->gst_rate );
                            $this->excel->getActiveSheet()->SetCellValue('Z' . $row, $sales_items_data->SGST);
                            $this->excel->getActiveSheet()->SetCellValue('AA' . $row, $sales_items_data->IGST != '0.0000' ? $sales_items_data->gst_rate : 0 );
                            $this->excel->getActiveSheet()->SetCellValue('AB' . $row, $sales_items_data->IGST);
                            $this->excel->getActiveSheet()->SetCellValue('AC' . $row, $sales_items_data->subtotal);

                            $cgst += $sales_items_data->CGST;
                            $sgst += $sales_items_data->SGST;
                            $igst += $sales_items_data->IGST;
                            $totalSubtotal += $sales_items_data->subtotal;
                            $row++;
                        }//end foreach
                    }//end if.
                    //exit;
                    $this->excel->getActiveSheet()->getStyle("A" . $row . ":AC" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

                    $total += $sale_data->grand_total;
                    $paid += $sale_data->paid;
                    $total_tax += $sale_data->total_tax;
                    $balance += $sale_data->balance;
                    $total_taxable_amt += $sale_data->taxable_amt;
                }//end outer foreach

                $this->excel->getActiveSheet()->getStyle("A" . $row . ":AC" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->getStyle("A" . $row . ":AC" . $row)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                $this->excel->getActiveSheet()->SetCellValue('H' . $row, 'Total Calculated Value:');
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $total);
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $total_taxable_amt);
                $this->excel->getActiveSheet()->SetCellValue('K' . $row, $total_tax);
                $this->excel->getActiveSheet()->SetCellValue('L' . $row, $paid);
                $this->excel->getActiveSheet()->SetCellValue('M' . $row, $balance);
                $this->excel->getActiveSheet()->SetCellValue('W' . $row, 'Total CGST:');
                $this->excel->getActiveSheet()->SetCellValue('X' . $row, $cgst);
                $this->excel->getActiveSheet()->SetCellValue('Y' . $row, 'Total SGST:');
                $this->excel->getActiveSheet()->SetCellValue('Z' . $row, $sgst);
                $this->excel->getActiveSheet()->SetCellValue('AA' . $row, 'Total IGST:');
                $this->excel->getActiveSheet()->SetCellValue('AB' . $row, $igst);
                $this->excel->getActiveSheet()->SetCellValue('AC' . $row, $totalSubtotal);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(40);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('O')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('P')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('Q')->setWidth(12);
                $this->excel->getActiveSheet()->getColumnDimension('R')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('S')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('T')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('U')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('V')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('W')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('X')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('Y')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('Z')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('AA')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('AB')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('AC')->setWidth(15);

                $filename = 'sales_gst_new_report_' . $max_export_sales . '_' . time();
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($pdf) {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getDefaultStyle()->getAlignment()->applyFromArray(
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
                    $this->excel->getActiveSheet()->getStyle('F2:F' . ($row + 1))->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if (img) {
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/sales_gst_report.pdf', __FILE__));
                    redirect("reports/create_image/sales_gst_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            $this->datatables->select("DATE_FORMAT(sma_sales.date, '%Y-%m-%d %T') as date,sma_sales.invoice_no,
            sma_sales.reference_no as reference_no,biller,customer,state,
            IF(comp.gstn_no IS NULL or comp.gstn_no = '', '-', comp.gstn_no) as gstn_no,
            grand_total + rounding, (grand_total - total_tax ) as tax_able_amount,paid,
            (grand_total + rounding - paid) as balance, sma_payments.paid_by, payment_status,
            {$this->db->dbprefix('sales')}.id as id", FALSE)
                    ->add_column('HsnCode', '')
                    ->add_column('qty', '')
                    ->add_column('units', '')
                    ->add_column('CGST', '', '')
                    ->add_column('SGST', '')
                    ->add_column('IGST', '')
                    ->add_column('TaxableAmont', '')
                    ->from('sales')
                    ->join('companies comp', 'sales.customer_id=comp.id', 'left')
                    ->join('sma_payments ', 'sales.id=sma_payments.sale_id', 'left')
                    ->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');
            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->datatables->where('sales.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('sales.created_by', $user);
                    }
                }
            }
            if ($biller) {
                $this->datatables->where('sales.biller_id', $biller);
            }
            if ($customer) {
                $this->datatables->where('sales.customer_id', $customer);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('sales.warehouse_id IN(' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->datatables->like('sales.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            } else {
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . (date('Y') - 2) . date('-m') . date('-d ') . '00:00:00' . '" and "' . date('Y-m-d H:i:s') . '"');
            }

            if ($gstn_opt) {
                switch ($gstn_opt) {
                    case '-1':
                        $this->datatables->where("comp.gstn_no IS NULL OR comp.gstn_no = '' ");
                        break;

                    case '1':
                        $this->datatables->where("comp.gstn_no IS NOT NULL and comp.gstn_no != '' ");
                        break;

                    default:

                        break;
                }
            }
            if ($gstn_no) {
                $this->datatables->where("comp.gstn_no = '" . $gstn_no . "' ");
            }
            if ($SalesIds) {
                $this->datatables->where('sales.id in (' . $SalesIds . ')');
            }
            $this->datatables->group_by('sales.id');
            echo $this->datatables->generate();
        }
    }

    /** @return json for sales_item 12-21-2019 */
    public function getSalesItemsGst() {
        $id = $this->input->get('id');
        $return_option = array();
        $return_option['hsn_code'] = $this->reports_model->getSalesHsunt($id, 'hsn_code'); //HSN CODE 
        $return_option['qty'] = $this->reports_model->getSalesQty($id, 'quantity'); //Quatity 
        $return_option['units'] = $this->reports_model->getSalesHsunt($id, 'product_unit_code'); //Units
        $return_option['tax'] = $this->reports_model->getSalesTax($id); //Units
        $datacgst = $this->reports_model->getSalesAsGst($id, 'cgst'); //CGST 
        foreach ($datacgst as $dataitem1 => $tax1) {//sumgst
            $arr1[] = $tax1->sumgst;
        }
        $return_option['CGST'] = implode(", ", $arr1);

        $datasgst = $this->reports_model->getSalesAsGst($id, 'cgst'); //SGST 
        //print_r($datasgst);
        foreach ($datasgst as $dataitem2 => $tax2) {//sumgst
            $arr2[] = $tax2->sumgst;
        }
        $return_option['SGST'] = implode(", ", $arr2);

        $dataigst = $this->reports_model->getSalesAsGst($id, 'igst'); //IGST 
        foreach ($dataigst as $dataitem3 => $tax3) {//sumgst
            $arr3[] = $tax3->sumgst;
        }
        $return_option['IGST'] = implode(", ", $arr3);


        echo json_encode($return_option);
    }

    /** 23-1-2020* */
    /*     * Invoice Sales List For WarehouseSales Report 1-28-2020* */

    public function get_sales_invoice() {
        $start_date = trim($_GET['startdate']);
        $end_date = trim($_GET['enddata']);
        $warehouse_id = $_GET['warehouse'];

        $sale_data = $this->reports_model->getSalesInvoice($start_date, $end_date, $warehouse_id);
        // print_r($sale_data);
        ?>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Gross Sale</th>
                        <th>Discount</th>
                        <th>Net Sales</th>
                        <th>Tax Amount</th>
                        <th>Received Amt</th>
                        <th>Balance Amt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalItems = $totalTax = $totaldiscount = $total = 0;
                    foreach ($sale_data as $key => $item) {
                        $totalItems += $item->qty;
                        $totalTax += $item->tax;
                        $totaldiscount += $item->discount;
                        $total += $item->net_total;
                        $gross = $item->net_total + $item->discount + $item->tax;
                        $netsale = $gross - $item->discount;
                        //$resales = $this->reports_model->getreturnsales($start_date,$end_date,$item->invoice_no);
                        $received_amt = $item->paid;
                        $balance_amt = ($netsale - $received_amt) + $item->rounding;
                        ?>
                        <tr>
                            <td><?= $item->date ?></td>
                            <td><?= $item->invoice_no ?></td>
                            <td><?= $item->customer ?></td>
                            <td><?= $this->sma->formatMoney($gross) ?></td>
                            <td><?= $this->sma->formatMoney($item->discount) ?></td>
                            <td><?= $this->sma->formatMoney($netsale) ?></td>
                            <td><?= $this->sma->formatMoney($item->tax ? number_format($item->tax, 2) : 0) ?></td>
                            <td><?= $this->sma->formatMoney($received_amt) ?></td>
                            <td><?= $this->sma->formatMoney($balance_amt) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
                 <!-- <tfoot>
                     <tr>
                         <th colspan="4">Totals</th>
                         <th><?= number_format($totalItems, 2) ?></th> 
                         <th>(Items)</th> 
                         <th></th> 
                         <th><?= $this->sma->formatMoney($totalTax) ?></th> 
                         <th><?= $this->sma->formatMoney($totaldiscount) ?></th> 
                         <th><?= $this->sma->formatMoney($total) ?></th>
                     </tr>
                 </tfoot>-->
            </table>
        </div>

        <?php
    }

    public function getChallansReport($pdf = NULL, $xls = NULL, $img = NULL) {
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

            /* $this->db->select("date,reference_no, biller, customer, GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('order_items') . ".product_name, ' (', " . $this->db->dbprefix('sale_items') . ".quantity, ')') SEPARATOR '\n') as iname, grand_total, paid, payment_status", FALSE)->from('sales')->join('sale_items', 'sale_items.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left')->group_by('sales.id')->order_by('sales.date desc'); */

            $this->db->select("orders.id as challan_id, orders.date as date, orders.reference_no, orders.invoice_no, orders.biller, orders.customer, orders.grand_total, orders.paid, orders.rounding, orders.payment_status", FALSE)->from('orders')->join('order_items', 'order_items.sale_id=orders.id', 'left')->join('warehouses', 'warehouses.id=orders.warehouse_id', 'left');

            /* $this->db->select("sales.id as sale_id,sales.date as date,sales.reference_no, sales.biller, sales.customer, sales.grand_total, sales.paid, sales.rounding,sales.payment_status", FALSE)->from('sales')->group_by('sales.id')->order_by('sales.date desc'); */

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->db->where('orders.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('orders.created_by', $user);
                    }
                }
            }
            if ($product) {
                $this->db->where('order_items.product_id', $product);
            }
            $this->db->group_by('orders.id');
            $this->db->order_by('orders.date desc');
            if ($serial) {
                $this->db->like('order_items.serial_no', $serial);
            }
            if ($biller) {
                $this->db->where('orders.biller_id', $biller);
            }
            if ($customer) {
                $this->db->where('orders.customer_id', $customer);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('orders.warehouse_id IN(' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->db->like('orders.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->db->where($this->db->dbprefix('orders') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            $this->db->where('orders.sale_as_chalan', 1);

            if ($limit != '' && $start != '') {
                $this->db->limit($limit, $start);
            }
            $q = $this->db->get();
            $data_sales = [];
            $products = '';
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    if (!in_array($row->challan_id, $data_sales)) {
                        $data_sales[] = $row->challan_id;
                    }

                    $id = $row->challan_id;
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

                $ChallansItems = $this->reports_model->getOrderItemsByOrderIds($uniqueSalesIds, $products);

                if (is_array($ChallansItems)) {
                    foreach ($ChallansItems as $key => $SaleItemsRow) {
                        //Sales Items Details
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['name'] = $SaleItemsRow->product_name;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['quantity'] = $SaleItemsRow->quantity;
                        $data[$SaleItemsRow->sale_id]['items'][$SaleItemsRow->items_id]['unit_price'] = $SaleItemsRow->unit_price;
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

                $this->excel->getActiveSheet()->getStyle("A1:M1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:M1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Challans Report');
                $this->excel->getActiveSheet()->setTitle(lang('challans_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('challan_no'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('biller'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('customer'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('product'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Varient'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Price'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('balance'));
                $this->excel->getActiveSheet()->SetCellValue('M2', lang('payment_status'));

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
                            $this->excel->getActiveSheet()->SetCellValue('I' . $row, $sales_items_data->unit_price);
                            $row++;
                        }//end foreach
                        $this->excel->getActiveSheet()->SetCellValue('J' . $rowitems, $data_row->grand_total);
                        $this->excel->getActiveSheet()->SetCellValue('K' . $rowitems, $data_row->paid);
                        $this->excel->getActiveSheet()->SetCellValue('L' . $rowitems, ($data_row->grand_total - $data_row->paid));
                        $this->excel->getActiveSheet()->SetCellValue('M' . $rowitems, lang($data_row->payment_status));
                    }//end if.

                    $total += $data_row->grand_total + $data_row->rounding;
                    $paid += $data_row->paid;
                    $balance += ($data_row->grand_total + $data_row->rounding - $data_row->paid);
                    //$row++;
                }
                $this->excel->getActiveSheet()->getStyle("J" . $row . ":L" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $total);
                $this->excel->getActiveSheet()->SetCellValue('K' . $row, $paid);
                $this->excel->getActiveSheet()->SetCellValue('L' . $row, $balance);

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
                $this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
                $filename = 'challans_report';
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/challans_report.pdf', __FILE__));
                    redirect("reports/create_image/challans_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $si = "( SELECT {$this->db->dbprefix('order_items')}.sale_id, {$this->db->dbprefix('order_items')}.product_id, {$this->db->dbprefix('order_items')}.serial_no, GROUP_CONCAT(CONCAT('',{$this->db->dbprefix('order_items')}.product_name, IF({$this->db->dbprefix('product_variants')}.name <> 'NULL',CONCAT(' (',{$this->db->dbprefix('product_variants')}.name,')'),''), CONCAT('( Rs',ROUND({$this->db->dbprefix('order_items')}.subtotal,2),')'), '-', ROUND({$this->db->dbprefix('order_items')}.quantity)) SEPARATOR ',\n') as item_nane from {$this->db->dbprefix('order_items')} ";
            $si .= "LEFT JOIN {$this->db->dbprefix('product_variants')} ON {$this->db->dbprefix('order_items')}.option_id = {$this->db->dbprefix('product_variants')}.id";

            $si .= " left Join {$this->db->dbprefix('orders')} ON {$this->db->dbprefix('orders')}.id = {$this->db->dbprefix('order_items')}.sale_id ";

            if ($product) {
                $si .= " WHERE {$this->db->dbprefix('order_items')}.product_id = {$product} ";
                $si .= " AND {$this->db->dbprefix('orders')}.sale_as_chalan = 1 ";
            } else {
                $si .= " WHERE {$this->db->dbprefix('orders')}.sale_as_chalan = 1 ";
            }

            $si .= " GROUP BY {$this->db->dbprefix('order_items')}.sale_id ) FSI";
            $this->load->library('datatables');

            $this->datatables->select("DATE_FORMAT(date, '%Y-%m-%d %T') as date, reference_no, invoice_no,biller, customer, sma_warehouses.name ,  FSI.item_nane as iname, grand_total+rounding, paid, (grand_total+rounding -paid) as balance, payment_status, {$this->db->dbprefix('orders')}.id as id", FALSE)
                    ->from('orders')
                    ->join($si, 'FSI.sale_id=orders.id', 'right')
                    ->join('warehouses', 'warehouses.id=orders.warehouse_id', 'left');
            // ->group_by('orders.id');

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->datatables->where('orders.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('orders.created_by', $user);
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
                $this->datatables->where('orders.biller_id', $biller);
            }
            if ($customer) {
                $this->datatables->where('orders.customer_id', $customer);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('orders.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($reference_no) {
                $this->datatables->like('orders.reference_no', $reference_no, 'both');
            }
            if ($start_date) {
                $this->datatables->where($this->db->dbprefix('orders') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            $this->db->where($this->db->dbprefix('orders') . '.sale_as_chalan', 1);

            echo $this->datatables->generate();
        }
    }

    public function challans() {
        $this->sma->checkPermissions('sales');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['salecount'] = $this->getCountChallans();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('challan_report'), 'bc' => $bc);
        $this->page_construct('reports/challans', $meta, $this->data);
    }

    public function getCountChallans() {

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
        $this->db->select("orders.id", FALSE)->from('orders')
                ->join('order_items', 'order_items.sale_id=orders.id', 'left')
                ->join('warehouses', 'warehouses.id=orders.warehouse_id', 'left')
                ->group_by('orders.id')
                ->order_by('orders.date desc');

        if ($this->Owner || $this->Admin) {
            if ($this->input->get('user')) {
                $this->db->where('orders.created_by', $this->input->get('user'));
            }
        } else {
            if ($this->session->userdata('view_right') == '0') {
                if ($user) {
                    $this->db->where('orders.created_by', $user);
                }
            }
        }
        if ($product) {
            $this->db->where('order_items.product_id', $product);
        }
        if ($serial) {
            $this->db->like('order_items.serial_no', $serial);
        }
        if ($biller) {
            $this->db->where('orders.biller_id', $biller);
        }
        if ($customer) {
            $this->db->where('orders.customer_id', $customer);
        }
        if ($warehouse) {
            $getwarehouse = str_replace("_", ",", $warehouse);
            $this->db->where('orders.warehouse_id IN(' . $getwarehouse . ')');
        }
        if ($reference_no) {
            $this->db->like('orders.reference_no', $reference_no, 'both');
        }
        if ($start_date) {
            $this->db->where('DATE(' . $this->db->dbprefix('orders') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
        }

        $this->db->where('orders.sale_as_chalan', 1);

        $q = $this->db->get();
        if ($q->num_rows() > 0) {

            $data = $q->num_rows();

            return $data;
        }

        return FALSE;
    }

    /**
     * Uprban Piper Daily Reports
     * @param type $warehouse_id
     * @param type $year
     * @param type $month
     * @param type $pdf
     * @param type $user_id
     */
    public function daily_sales_up($warehouse_id = NULL, $year = NULL, $month = NULL, $pdf = NULL, $user_id = NULL) {
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

        $config = array('show_next_prev' => TRUE, 'next_prev_url' => site_url('reports/daily_sales_up/' . ($this->data['sel_warehouse'] ? $key[0] : 0)), 'month_type' => 'long', 'day_type' => 'long');

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
        $sales = $user_id ? $this->reports_model->getStaffDailySalesUP($user_id, $year, $month, $warehouse_id) : $this->reports_model->getDailySalesUP($year, $month, $warehouse_id);
        $sales_w = $user_id ? $this->reports_model->getStaffDailySalesUP_w($user_id, $year, $month, $warehouse_id) : $this->reports_model->getDailySalesUP_w($year, $month, $warehouse_id);

        if (!empty($sales)) {
            foreach ($sales as $sale) {
                $daily_sale[$sale->date] = "<table class='table table-bordered table-hover table-striped table-condensed data' style='margin:0;'><tr><td>" . lang("discount") . "</td><td>" . $this->sma->formatMoney($sale->discount) . "</td></tr><tr><td>" . lang("shipping") . "</td><td>" . $this->sma->formatMoney($sale->shipping) . "</td></tr><tr style='cursor: pointer' onClick='getsaleitemstaxes(" . $year . "," . $month . "," . $sale->date . ")'><td>" . lang("product_tax") . " <i class='fa fa-list-alt' aria-hidden='true'></i></td><td>" . $this->sma->formatMoney($sale->tax1) . "</td></tr><tr><td>" . lang("order_tax") . "</td><td>" . $this->sma->formatMoney($sale->tax2) . "</td></tr><tr><td>" . lang("total") . "</td><td>" . $this->sma->formatMoney($sale->total) . "</td></tr><tr><td>Items</td><td onClick='getsaleitems(" . $year . "," . $month . "," . $sale->date . ")'><i class='fa fa-list-alt' aria-hidden='true'></i></td></tr></table>";
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
            $this->excel->getActiveSheet()->SetCellValue('H2', lang('Warehouse'));
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

            $this->excel->getActiveSheet()->getStyle("A2:H" . ($row - 1))->applyFromArray($style);

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
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Daily_Sales_Urban_Piper_Report')));
        $meta = array('page_title' => lang('Urban Piper Report'), 'bc' => $bc);
        $this->page_construct('reports/daily_up', $meta, $this->data);
    }

    /*  daily Urbin_piper 1-4-2020 */

    public function daily_Urbin_piper() {
        $date = $_GET['date'];
        $warehouse_id = $_GET['active_warehouse_id'];
        if (empty($date))
            return FALSE;

        $sale_data = $this->reports_model->getDailyUrbinpiper($date);
        ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Channel</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    if (!empty($sale_data)) {
                        foreach ($sale_data as $key => $item) {
                            $total += $item->total;
                            ?>
                            <tr>
                                <td class="text-center"><?= $item->invoice; ?></td>
                                <td class="text-center"><?= $item->up_channel ?></td>
                                <td class="text-center">Rs. <?= number_format($item->total, 2); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-right">Total</th>
                        <th class="text-center">Rs. <?= number_format($total, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php
    }

    /*     * *********************Category Report Start******************************** */

    function categories_report() {
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
        $this->data['simple_datatable'] = 'simple_datatable';
        $this->page_construct('reports/categories_report', $meta, $this->data);
    }

    function getCategoriesDetailReport($pdf = NULL, $xls = NULL, $img = NULL) {

        $this->sma->checkPermissions('products', TRUE);
        $Datas['warehouse'] = $Data['warehouse'] = $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $Datas['category'] = $Data['category'] = $category = $this->input->get('category') ? $this->input->get('category') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $Datas['start_date'] = $Data['start_date'] = $start_date = $start_date ? $this->sma->fld($start_date) : '';
        $Datas['end_date'] = $Data['end_date'] = $end_date = $end_date ? $this->sma->fld($end_date) : date('Y-m-d');
        if ($pdf || $xls || $img) {
            $this->db->select('e.name AS parent_name, e.id AS parent_id, r.id AS cid, r.name AS child_name')->from("sma_categories r")->join('sma_categories e', "e.id=r.parent_id", 'left');

            if ($category) {
                $this->db->where('r.id', $category);
            }
            $this->db->order_by('COALESCE(parent_name, child_name)');

            $q = $this->db->get();
            $data = array();
            if ($q->num_rows() > 0) {
                foreach (($q->result()) as $row) {
                    $Row = $this->getCategoriesWiseDataReportExport($warehouse, $row->cid, $start_date, $end_date);
                    $PurchasedQty = 0;
                    $SoldQty = 0;
                    $TotalPurchase = 0;
                    $TotalSales = 0;
                    $ProfitLoss = 0;
                    $CatCode = '';
                    //print_r($Row);
                    foreach ($Row as $val) {
                        $CatCode = $val->code;
                        $PurchasedQty = $val->PurchasedQty;
                        $SoldQty = $val->SoldQty;
                        $TotalPurchase = $val->TotalPurchase;
                        $TotalSales = $val->TotalSales;
                    }
                    //if($PurchasedQty!='0' || $SoldQty!='0' ){
                    //print_r($Rowdecode);
                    if ($row->parent_name == '')
                        $category_name = $row->child_name;
                    else
                        $category_name = '';
                    if ($row->parent_name != '')
                        $sub_category_name = $row->child_name;
                    else
                        $sub_category_name = '';
                    $data[] = array(
                        'cid' => $row->cid,
                        'code' => $CatCode,
                        'parent_cat' => $category_name,
                        'child_cat' => $sub_category_name,
                        'PurchasedQty' => $PurchasedQty,
                        'SoldQty' => $SoldQty,
                        'TotalPurchase' => $TotalPurchase,
                        'TotalSales' => $TotalSales,
                    );
                    //}
                }
            }
            //$data = (object) $data1;
            //echo '<pre>';
            //print_r($data);
            //exit;

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
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Subcategory'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('purchased'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('sold'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('purchased_amount'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('sold_amount'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('profit_loss'));

                $row = 3;
                $sQty = 0;
                $pQty = 0;
                $sAmt = 0;
                $pAmt = 0;
                $pl = 0;
                //print_r($data);
                foreach ($data as $keys => $data_row) {
                    $profit = $data_row['TotalSales'] - $data_row['TotalPurchase'];
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row['code']);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row['parent_cat']);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row['child_cat']);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row['PurchasedQty']);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row['SoldQty']);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row['TotalPurchase']);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row['TotalSales']);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $profit);
                    $pQty += $data_row['PurchasedQty'];
                    $sQty += $data_row['SoldQty'];
                    $pAmt += $data_row['TotalPurchase'];
                    $sAmt += $data_row['TotalSales'];
                    $pl += $profit;
                    $row++;
                }
                $this->excel->getActiveSheet()->getStyle("C" . $row . ":G" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $pQty);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $sQty);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $pAmt);
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $sAmt);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $pl);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);

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
            /*
              $this->load->library('datatables');
              $this->datatables->select('r.id as cid, r.code, ( CASE r.parent_id WHEN 0 THEN r.name END) as parent_cat, (CASE WHEN e.id != 0 THEN r.name END) as child_cat, r.name AS child_name, e.name AS parent_name,
              e.id AS main_parent_id')->from("sma_categories r")->join('sma_categories e', "(e.id=r.parent_id)", 'left');

              if ($category) {
              $this->datatables->where('r.id', $category);
              }
              $this->db->order_by('COALESCE(parent_name, child_name), child_name');
              //$this->datatables->order_by('r.id');
              $this->datatables->unset_column('parent_name, child_name, parent_id');
              $this->datatables->add_column('Purchased', '');
              $this->datatables->add_column('Sold', '');
              $this->datatables->add_column('Purchased Amount', '');
              $this->datatables->add_column('Sold Amount', '');
              $this->datatables->add_column('Profit & Loss', '');
              echo $this->datatables->generate(); */
            if (isset($_REQUEST['order'])) {
                foreach ($_REQUEST['order'] as $order) {
                    $Data['column'] = $order['column'];
                    $Data['order_by'] = $order['dir'];
                }
            }

            $total = $this->reports_model->category_count_data($Data, isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
            $data = array();
            $Result = $this->reports_model->getCategoryLists($Data, isset($_REQUEST['start']) ? $_REQUEST['start'] : "", isset($_REQUEST['length']) ? $_REQUEST['length'] : "", isset($_REQUEST['search']) ? $_REQUEST['search'] : "");

            foreach ($Result->result() as $key => $item) {
                $Data['category'] = $item->cid;
                $ResultData = $this->reports_model->getCategoryListDetails($Data);
                //print_r($ResultData);
                //echo $ResultData->SoldQty.' '.$ResultData->PurchasedQty.'<br>';
                //if($ResultData->PurchasedQty!='0' || $ResultData->SoldQty!='0' ){
                if (!empty($ResultData)) {
                    $row_data['parent_name'] = $item->parent_name;
                    if ($item->parent_name == '')
                        $row_data['category_name'] = $item->child_name;
                    else
                        $row_data['category_name'] = '';
                    if ($item->parent_name != '')
                        $row_data['sub_category_name'] = $item->child_name;
                    else
                        $row_data['sub_category_name'] = '';
                    $row_data['category_code'] = $ResultData->code;
                    $row_data['purchased'] = $ResultData->PurchasedQty;
                    $row_data['TotalPurchase'] = $ResultData->TotalPurchase;
                    $row_data['SoldQty'] = $ResultData->SoldQty;
                    $row_data['TotalSales'] = $ResultData->TotalSales;
                    $row_data['Profit'] = $ResultData->Profit;
                    $data[] = $row_data;
                }
            }
            //echo '<pre>';
            //print_r($data);
            //exit;
            $DataArray = array('draw' => $_REQUEST['draw'], 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data);
            echo json_encode($DataArray);
        }
    }

    function getCategoriesWiseDataReportExport($warehouse, $category, $start_date, $end_date) {
        $this->db->select("id")->from('categories')->where('id', $category)->where('parent_id', 0);
        $q = $this->db->get()->row();
        $cat_id = $q->id;
        if ($cat_id == $category) {
            $cat_id = 'category_id';
        } else {
            $cat_id = 'subcategory_id';
        }
        $pp = "( SELECT pp." . $cat_id . " as category, SUM( pi.quantity ) purchasedQty, SUM( pi.subtotal ) totalPurchase from {$this->db->dbprefix('products')} pp
                left JOIN " . $this->db->dbprefix('purchase_items') . " pi ON pp.id = pi.product_id
                left join " . $this->db->dbprefix('purchases') . " p ON p.id = pi.purchase_id ";
        $sp = "( SELECT sp." . $cat_id . " as category, SUM( si.quantity ) soldQty, SUM( si.subtotal ) totalSale from {$this->db->dbprefix('products')} sp
                left JOIN " . $this->db->dbprefix('sale_items') . " si ON sp.id = si.product_id
                left join " . $this->db->dbprefix('sales') . " s ON s.id = si.sale_id ";
        if ($start_date || $warehouse) {
            $pp .= " WHERE ";
            $sp .= " WHERE ";
            if ($start_date) {
                $pp .= " (DATE(p.date) between '{$start_date}' AND '{$end_date}') ";
                $sp .= " (DATE(s.date) between '{$start_date}' AND '{$end_date}') ";
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
        $pp .= " GROUP BY pp." . $cat_id . ") PCosts";
        $sp .= " GROUP BY sp." . $cat_id . ") PSales";
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
        $data = array();
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
        }
        return $data;
    }

    function getCategoriesWiseDataReport() {
        $this->sma->checkPermissions('products', TRUE);
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $category = $this->input->get('category') ? $this->input->get('category') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $this->db->select("id")->from('categories')->where('id', $category)->where('parent_id', 0);
        $q = $this->db->get()->row();
        $cat_id = $q->id;
        if ($cat_id == $category) {
            $cat_id = 'category_id';
        } else {
            $cat_id = 'subcategory_id';
        }
        $pp = "( SELECT pp." . $cat_id . " as category, SUM( pi.quantity ) purchasedQty, SUM( pi.subtotal ) totalPurchase from {$this->db->dbprefix('products')} pp
                left JOIN " . $this->db->dbprefix('purchase_items') . " pi ON pp.id = pi.product_id
                left join " . $this->db->dbprefix('purchases') . " p ON p.id = pi.purchase_id ";
        $sp = "( SELECT sp." . $cat_id . " as category, SUM( si.quantity ) soldQty, SUM( si.subtotal ) totalSale from {$this->db->dbprefix('products')} sp
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
        $pp .= " GROUP BY pp." . $cat_id . ") PCosts";
        $sp .= " GROUP BY sp." . $cat_id . ") PSales";
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
        $data = array();
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
    }

    /*     * *********************Category Report End ********************************* */


    /*     * *************Overdue Payment*************** */

    function overdue_sales() {
        $Customer = ' and s.customer_id = ' . $this->uri->segment(3);
        ;
        $this->data['POSDATA'] = $this->reports_model->get_overdue_sale($Customer);
        //print_r($this->data);
        // exit;
        $this->load->view($this->theme . 'reports/overdue_sale', $this->data);
    }

    function overdue_payments() {
        //$this->sma->checkPermissions('payments');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $this->data['customer'] = $this->site->getAllCompanies('customer');
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Overdue_Payment_Report')));
        $meta = array('page_title' => lang('Overdue_Payment_Report'), 'bc' => $bc);
        $this->page_construct('reports/overdue_payments', $meta, $this->data);
    }

    function getOverduePaymentsReport($pdf = NULL, $xls = NULL, $img = NULL) {
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

//        if($start_date)
//        {
//            $start_date = $this->sma->fsd($start_date);
//            $end_date = $this->sma->fsd($end_date);
//        }
        if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
            $user = $this->session->userdata('user_id');
        }
        if ($pdf || $xls || $img) {
            $this->db->select($this->db->dbprefix('sales') . ".customer_id," . $this->db->dbprefix('sales') . ".customer, sum(" . $this->db->dbprefix('sales') . ".grand_total+" . $this->db->dbprefix('sales') . ".rounding) as grand_total, sum(" . $this->db->dbprefix('sales') . ".paid) as paid, sum(" . $this->db->dbprefix('sales') . ".grand_total+" . $this->db->dbprefix('sales') . ".rounding-" . $this->db->dbprefix('sales') . ".paid) as balance ")
                    ->from('sales')
                    //->join('payments', 'payments.sale_id=sales.id', 'left')
                    ->group_by('sales.customer_id');
            $this->db->where("(sales.payment_status='partial' or sales.payment_status='due' or sales.payment_status='pending')");

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

            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('sales.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($biller) {
                $this->db->where('sales.biller_id', $biller);
            }

            if ($payment_ref) {
                $this->db->like('payments.reference_no', $payment_ref, 'both');
            }
            if ($sale_ref) {
                $this->db->like('sales.reference_no', $sale_ref, 'both');
            }

            if ($start_date) {
                //$this->db->where($this->db->dbprefix('sales') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) >= "' . $start_date . '"');
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) <= "' . $end_date . '"');
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
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Overdue Payments Report');

                $this->excel->getActiveSheet()->setTitle(lang('Overdue_payments_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Customer_Name'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Total_Amount'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Paid_Amount'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Balance'));

                $row = 3;
                $total = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->customer);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->paid);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->balance);

                    /* if($data_row->type == 'sent')
                      { //$data_row->type == 'returned' ||
                      $total -= $data_row->amount;
                      }else
                      {
                      $total += $data_row->amount;
                      } */
                    $row++;
                }
                //$this->excel->getActiveSheet()->getStyle("D" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                //$this->excel->getActiveSheet()->SetCellValue('D' . $row, $total);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);

                $filename = 'Overdue_payments_report';
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

            //

            $this->load->library('datatables');
            $this->datatables->select($this->db->dbprefix('sales') . ".customer_id," . $this->db->dbprefix('sales') . ".customer, sum(" . $this->db->dbprefix('sales') . ".grand_total+" . $this->db->dbprefix('sales') . ".rounding) as grand_total, sum(" . $this->db->dbprefix('sales') . ".paid) as paid, sum(" . $this->db->dbprefix('sales') . ".grand_total+" . $this->db->dbprefix('sales') . ".rounding-" . $this->db->dbprefix('sales') . ".paid) as balance ")
                    ->from('sales')
                    // ->join('payments', 'payments.sale_id=sales.id', 'left')
                    ->group_by('sales.customer_id');
            $this->datatables->where("(sales.payment_status='partial' or sales.payment_status='due' or sales.payment_status='pending')");
            //$this->datatables->where('(sales.payment_status', 'partial');
            //$this->datatables->or_where('sales.payment_status', 'due');
            //$this->datatables->or_where('sales.payment_status', 'pending');
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
            if ($customer) {
                $this->datatables->where('sales.customer_id', $customer);
            }
            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('sales.warehouse_id IN (' . $getwarehouse . ')');
            }
            if ($biller) {
                $this->datatables->where('sales.biller_id', $biller);
            }

            if ($payment_ref) {
                $this->datatables->like('payments.reference_no', $payment_ref, 'both');
            }
            if ($sale_ref) {
                $this->datatables->like('sales.reference_no', $sale_ref, 'both');
            }
            if ($start_date) {
                //$this->datatables->where($this->db->dbprefix('sales') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) >= "' . $start_date . '"');
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) <= "' . $end_date . '"');
            }
            echo $this->datatables->generate();
        }
    }

    /*     * *************Overdue Payment*************** */

    /**
     * Sales Due Report
     */
    function sales_due() {
        $this->sma->checkPermissions('sales');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['salecount'] = $this->getCountSales();
        $this->data['customer'] = $this->reports_model->getCustomerCompanies();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Due Sales Report')));
        $meta = array('page_title' => lang('Due Sales Report'), 'bc' => $bc);
        $this->page_construct('reports/sales_due', $meta, $this->data);
    }

    function getSalesReportDue($pdf = NULL, $xls = NULL, $img = NULL) {
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
                    $this->db->where('sales.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('sales.created_by', $user);
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
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            $this->db->where_in($this->db->dbprefix('sales') . '.payment_status', ['pending', 'partial', 'due']);
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
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Due Sales Report');
                $this->excel->getActiveSheet()->setTitle(lang('Due_sales_report'));
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
                $filename = 'Due_sales_report';
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
            $this->datatables->select("{$this->db->dbprefix('sales')}.id as id, DATE_FORMAT(date, '%d/%m/%Y %T') as date, reference_no, invoice_no,biller, customer, FSI.item_nane as iname, grand_total+rounding, paid, (grand_total+rounding -paid) as balance, payment_status ", FALSE)->from('sales')->join($si, 'FSI.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');
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
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            $this->datatables->where_in('sales.payment_status', ['pending', 'partial', 'due']);
            echo $this->datatables->generate();
        }
    }

    /**
     * End Sales Due Report
     */

    /**
     * Purchase Due Report
     */
    function purchases_due() {
        $this->sma->checkPermissions('purchases');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Due Purchases Report')));
        $meta = array('page_title' => lang('Due Purchases Report'), 'bc' => $bc);
        $this->page_construct('reports/purchases_due', $meta, $this->data);
    }

    function getPurchasesReportDue($pdf = NULL, $xls = NULL) {
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

            $this->db->select("" . $this->db->dbprefix('purchases') . ".date, reference_no, " . $this->db->dbprefix('warehouses') . ".name as wname, supplier, GROUP_CONCAT(CONCAT(" . $this->db->dbprefix('purchase_items') . ".product_name, ' (', " . $this->db->dbprefix('purchase_items') . ".quantity, ')') SEPARATOR '\n') as iname, (grand_total+rounding) as grand_total, paid, " . $this->db->dbprefix('purchases') . ".status, payment_status", FALSE)->from('purchases')->join('purchase_items', 'purchase_items.purchase_id=purchases.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left')->group_by('purchases.id')->order_by('purchases.date desc');

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

            $this->db->where_in('purchases.payment_status', ['partial', 'pending', 'due']);
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
                $this->db->where('DATE(' . $this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Due Purchases Report');

                $this->excel->getActiveSheet()->setTitle(lang('Due_purchase_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('warehouse'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('supplier'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('product_qty'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('balance'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('status'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('payment_status'));
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
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row->payment_status);
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
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
                $filename = 'due_purchase_report';
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
            $this->datatables->select("DATE_FORMAT({$this->db->dbprefix('purchases')}.date, '%Y-%m-%d %T') as date, reference_no, {$this->db->dbprefix('warehouses')}.name as wname, supplier, (FPI.item_nane) as iname, (grand_total+rounding) as grand_total, paid, (grand_total+rounding-paid) as balance, {$this->db->dbprefix('purchases')}.status, payment_status, {$this->db->dbprefix('purchases')}.id as id", FALSE)->from('purchases')->join($pi, 'FPI.purchase_id=purchases.id', 'left')->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left');
            // ->group_by('purchases.id');

            if ($this->Owner || $this->Admin) {
                if ($user) {
                    $this->datatables->where('purchases.created_by', $user);
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('purchases.created_by', $user);
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
                $this->datatables->where('DATE(' . $this->db->dbprefix('purchases') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            $this->datatables->where_in('purchases.payment_status', ['partial', 'pending', 'due']);
            echo $this->datatables->generate();
        }
    }

    /**
     * End Purchase Due Reports
     */
    /* product_profit_and_loss  30-6-2020 */

    function products_profitloss() {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->data['brands'] = $this->site->getAllBrands();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_report')));
        $meta = array('page_title' => lang('products_report'), 'bc' => $bc);
        $this->page_construct('reports/products_profiteloss', $meta, $this->data);
    }

    function getProductsReport_Profitloss($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('sales', TRUE);
        $start = '';
        $limit = '';
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $category = $this->input->get('category') ? $this->input->get('category') : NULL;
        $style_code = $this->input->get('style_code') ? $this->input->get('style_code') : NULL;
        $brand = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $subcategory = $this->input->get('subcategory') ? $this->input->get('subcategory') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $biller = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

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



        $pi = "( SELECT {$this->db->dbprefix('categories')}.name as categories,{$this->db->dbprefix('brands')}.name as brand,{$this->db->dbprefix('products')}.id,{$this->db->dbprefix('products')}.category_id,{$this->db->dbprefix('products')}.subcategory_id,{$this->db->dbprefix('products')}.brand as brand_id,{$this->db->dbprefix('products')}.cost,(select name from sma_categories where id = {$this->db->dbprefix('products')}.subcategory_id) as subcatgory,{$this->db->dbprefix('products')}.code,{$this->db->dbprefix('products')}.article_code,{$this->db->dbprefix('products')}.price  from {$this->db->dbprefix('products')} ";

        $pi .= "LEFT JOIN {$this->db->dbprefix('categories')} ON {$this->db->dbprefix('products')}. category_id = {$this->db->dbprefix('categories')}.id";
        $pi .= " LEFT JOIN {$this->db->dbprefix('brands')} ON {$this->db->dbprefix('products')}.brand = {$this->db->dbprefix('brands')}.id) pr";


        $si = "( SELECT {$this->db->dbprefix('sale_items')}.sale_id, {$this->db->dbprefix('sale_items')}.product_id, {$this->db->dbprefix('sale_items')}.serial_no, {$this->db->dbprefix('sale_items')}.product_name,pr.code,pr.article_code,pr.cost,pr.price,pr.brand,pr.categories,pr.subcatgory,pr.brand_id,pr.category_id,pr.subcategory_id,(select name from sma_product_variants where  id = {$this->db->dbprefix('sale_items')}.option_id) as sizevarient,{$this->db->dbprefix('product_variants')}.name as varient,{$this->db->dbprefix('sale_items')}.item_tax,{$this->db->dbprefix('sale_items')}.item_discount,{$this->db->dbprefix('sale_items')}.tax,{$this->db->dbprefix('sale_items')}.unit_price,{$this->db->dbprefix('sale_items')}.real_unit_price,{$this->db->dbprefix('sale_items')}.invoice_total_net_unit_price,{$this->db->dbprefix('sale_items')}.net_unit_price from {$this->db->dbprefix('sale_items')} ";
        $si .= "LEFT JOIN {$this->db->dbprefix('product_variants')} ON {$this->db->dbprefix('sale_items')}.option_id = {$this->db->dbprefix('product_variants')}.id";
        $si .= " LEFT JOIN  $pi ON {$this->db->dbprefix('sale_items')}.product_id = pr.id  ) FSI";

        if ($pdf || $xls || $img) {


            $this->db->select("DATE_FORMAT(date, '%Y-%m-%d') as date,FSI.code as barcode,FSI.product_name as product_name,FSI.categories,FSI.subcatgory,FSI.brand,FSI.article_code as style_Code,FSI.sizevarient as sizevarient,IF({$this->db->dbprefix('sales')}.sale_status='returned',-(FSI.cost),FSI.cost)  as purchasecost,(FSI.invoice_total_net_unit_price) as sellingPrice,FSI.item_discount as discount,FSI.item_tax as taxamt,FSI.tax as taxrate,IF({$this->db->dbprefix('sales')}.sale_status='returned',-(abs(FSI.invoice_total_net_unit_price) - abs(FSI.item_discount) - FSI.cost ),FSI.invoice_total_net_unit_price - FSI.item_discount - FSI.cost) as grossprofit, IF({$this->db->dbprefix('sales')}.sale_status='returned',-(abs(FSI.invoice_total_net_unit_price) - abs(FSI.item_discount) - FSI.cost - abs(FSI.item_tax)),FSI.invoice_total_net_unit_price - FSI.item_discount - FSI.cost - FSI.item_tax) as netprofite,, {$this->db->dbprefix('sales')}.id as id", FALSE)->from('sales')->join($si, 'FSI.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');


            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->db->where('sales.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('sales.created_by', $user);
                    }
                }
            }

            if ($category) {
                $this->db->where('FSI.category_id', $category);
            }

            if ($subcategory) {
                $this->db->where('FSI.subcategory_id', $subcategory);
            }

            if ($brand) {
                $this->db->where('FSI.brand_id', $brand);
            }
            if ($style_code) {
                $this->db->where('FSI.article_code', $style_code);
            }
            if ($biller) {
                $this->db->where('sales.biller_id', $biller);
            }

            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->db->where('sales.warehouse_id IN (' . $getwarehouse . ')');
            }

            if ($start_date) {
                $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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

                $this->excel->getActiveSheet()->getStyle("A1:O1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:O1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Product Profit and loss Report');
                $this->excel->getActiveSheet()->setTitle(lang('Product Profit and Loss Report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Barcode'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Category'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Sub_Category'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Brand'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('style_code'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('Size'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Cost'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Selling_Price'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('Discount'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('GST %'));
                $this->excel->getActiveSheet()->SetCellValue('M2', lang('GST'));
                $this->excel->getActiveSheet()->SetCellValue('N2', lang('Gross_Profit'));
                $this->excel->getActiveSheet()->SetCellValue('O2', lang('Net_Profit'));

                $row = 3;
                $purcost = 0;
                $proprice = 0;
                $sprice = 0;
                $gamt = 0;
                $grpft = 0;
                $ntpft = 0;
                $ppft = 0;
                $dis = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->barcode);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->product_name);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->categories);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->subcatgory);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->brand);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->style_Code);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->sizevarient);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->purchasecost);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row->sellingPrice);
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $data_row->discount);
                    $this->excel->getActiveSheet()->SetCellValue('L' . $row, $data_row->taxrate);
                    $this->excel->getActiveSheet()->SetCellValue('M' . $row, $data_row->taxamt);
                    $this->excel->getActiveSheet()->SetCellValue('N' . $row, $data_row->grossprofit);
                    $this->excel->getActiveSheet()->SetCellValue('O' . $row, $data_row->netprofite);

                    $purcost += $data_row->purchasecost;
                    //$proprice += $data_row->product_price;
                    $sprice += $data_row->sellingPrice;
                    $gamt += $data_row->taxamt;
                    $dis += $data_row->discount;
                    $grpft += $data_row->grossprofit;
                    $ntpft += $data_row->netprofite;
                    //$ppft += $data_row->pureprofite;
                    $row++;
                }
                // $this->excel->getActiveSheet()->getStyle("C" . $row . ":G" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $purcost);
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $sprice);
                $this->excel->getActiveSheet()->SetCellValue('K' . $row, $dis);
                $this->excel->getActiveSheet()->SetCellValue('M' . $row, $gamt);
                $this->excel->getActiveSheet()->SetCellValue('N' . $row, $grpft);
                $this->excel->getActiveSheet()->SetCellValue('O' . $row, $ntpft);
                //$this->excel->getActiveSheet()->SetCellValue('P' . $row, $ppft);

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
                $filename = 'Products_Profite_And_Loss_report';
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/product_profit_and_loss.pdf', __FILE__));
                    redirect("reports/create_image/product_profit_and_loss.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');

            $this->datatables->select("DATE_FORMAT(date, '%Y-%m-%d') as date,FSI.code as barcode,FSI.product_name as product_name,FSI.categories,FSI.subcatgory,FSI.brand,FSI.article_code as style_Code,FSI.sizevarient as sizevarient,IF({$this->db->dbprefix('sales')}.sale_status='returned',-(FSI.cost),FSI.cost)  as purchasecost,(FSI.invoice_total_net_unit_price) as sellingPrice,FSI.item_discount as discount,FSI.item_tax as taxamt,FSI.tax as taxrate,IF({$this->db->dbprefix('sales')}.sale_status='returned',-(abs(FSI.invoice_total_net_unit_price) - abs(FSI.item_discount) - FSI.cost ),FSI.invoice_total_net_unit_price - FSI.item_discount - FSI.cost) as grossprofit, IF({$this->db->dbprefix('sales')}.sale_status='returned',-(abs(FSI.invoice_total_net_unit_price) - abs(FSI.item_discount) - FSI.cost - abs(FSI.item_tax)),FSI.invoice_total_net_unit_price - FSI.item_discount - FSI.cost - FSI.item_tax) as netprofite,, {$this->db->dbprefix('sales')}.id as id", FALSE)->from('sales')->join($si, 'FSI.sale_id=sales.id', 'left')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');
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

            if ($category) {
                $this->datatables->where('FSI.category_id', $category);
            }

            if ($subcategory) {
                $this->datatables->where('FSI.subcategory_id', $subcategory);
            }

            if ($brand) {
                $this->datatables->where('FSI.brand_id', $brand);
            }
            if ($style_code) {
                $this->datatables->where('FSI.article_code', $style_code);
            }
            if ($biller) {
                $this->datatables->where('sales.biller_id', $biller);
            }

            if ($warehouse) {
                $getwarehouse = str_replace("_", ",", $warehouse);
                $this->datatables->where('sales.warehouse_id IN (' . $getwarehouse . ')');
            }

            if ($start_date) {
                $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function products_costing($warehouse_id = NULL) {
        //$this->sma->checkPermissions('products_costing');
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

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_costing')));
        $meta = array('page_title' => lang('products_costings'), 'bc' => $bc);
        $this->page_construct('reports/products_costing', $meta, $this->data);
    }

    function getProductCosting($warehouse_id = NULL, $pdf = NULL, $xls = NULL, $img = NULL) {
        //  $this->sma->checkPermissions('products_costing', TRUE);


        if (!$this->Owner && !$warehouse_id) {
            $user = $this->site->getUser();
            $warehouse_id = $user->warehouse_id;
        }

        if ($pdf || $xls || $img) {

            if ($warehouse_id) {
                $getwarehouse = str_replace("_", ",", $warehouse_id);
                $this->db->select('product_code, product_name, sum(quantity) AS quantity, sum(quantity_balance) AS quantity_balance, AVG(unit_cost) AS unit_cost')
                        ->from('purchase_items')
                        ->where('purchase_id > 0', NULL)
                        ->where('warehouse_id IN (' . $getwarehouse . ')')
                        ->group_by('product_id');
            } else {

                $this->db->select('product_code, product_name, sum(quantity) AS quantity, sum(quantity_balance) AS quantity_balance, AVG(unit_cost) AS unit_cost')
                        ->from('purchase_items')
                        ->where('purchase_id > 0', NULL)
                        ->group_by('product_id');
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

                $this->excel->getActiveSheet()->getStyle("A1:E1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:E1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Costing (All Warehouses)');
                $this->excel->getActiveSheet()->setTitle(lang('products_costing'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('purchase quantity'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Balance quantity'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Avarage Cost'));

                $row = 3;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->product_code);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->product_name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->quantity);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->quantity_balance);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->unit_cost);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);

                $filename = 'products_costings';
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_costing.pdf', __FILE__));
                    redirect("reports/create_image/products_costing.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');

            if ($warehouse_id) {
                $getwarehouse = str_replace("_", ",", $warehouse_id);

                $this->datatables->select('product_code, product_name, sum(quantity) quantity, sum(quantity_balance) quantity_balance, AVG(unit_cost) AS unit_cost')
                        ->from('purchase_items')
                        ->where('purchase_id > 0', NULL)
                        ->where('warehouse_id IN (' . $getwarehouse . ')')
                        ->group_by('product_id');
            } else {

                $this->datatables->select('product_code, product_name, sum(quantity) AS quantity, sum(quantity_balance) AS quantity_balance, AVG(unit_cost) AS unit_cost')
                        ->from('purchase_items')
                        ->where('purchase_id > 0', NULL)
                        ->group_by('product_id');
            }

            echo $this->datatables->generate();
        }
    }

    /*     * End of report* */

    /*     * order Report* */

    function products_orderReport() {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();

        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Order_Report')));
        $meta = array('page_title' => lang('Order_Report'), 'bc' => $bc);
        $this->page_construct('reports/order_details', $meta, $this->data);
    }

    function getProductsOrderReport($pdf = NULL, $xls = NULL, $img = NULL) {
        $this->sma->checkPermissions('sales', TRUE);
        $start = '';
        $limit = '';
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $category = $this->input->get('category') ? $this->input->get('category') : NULL;

        $subcategory = $this->input->get('subcategory') ? $this->input->get('subcategory') : NULL;
        $ordertype = $this->input->get('ordertype') ? $this->input->get('ordertype') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;


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



        $pi = "( SELECT {$this->db->dbprefix('categories')}.name as categories,{$this->db->dbprefix('products')}.id,{$this->db->dbprefix('products')}.category_id,{$this->db->dbprefix('products')}.subcategory_id,{$this->db->dbprefix('products')}.cost,(select name from sma_categories where id = {$this->db->dbprefix('products')}.subcategory_id) as subcatgory,{$this->db->dbprefix('products')}.code,{$this->db->dbprefix('products')}.article_code,{$this->db->dbprefix('products')}.price  from {$this->db->dbprefix('products')} ";

        $pi .= "LEFT JOIN {$this->db->dbprefix('categories')} ON {$this->db->dbprefix('products')}. category_id = {$this->db->dbprefix('categories')}.id) pr";
        //$pi .= " LEFT JOIN {$this->db->dbprefix('brands')} ON {$this->db->dbprefix('products')}.brand = {$this->db->dbprefix('brands')}.id) pr";//(select name from sma_product_variants where group_id = 1 and id = {$this->db->dbprefix('order_items')}.option_id)


        $si = "( SELECT {$this->db->dbprefix('order_items')}.sale_id, {$this->db->dbprefix('order_items')}.product_id, {$this->db->dbprefix('order_items')}.serial_no, {$this->db->dbprefix('order_items')}.product_name,{$this->db->dbprefix('order_items')}.quantity,pr.code,pr.article_code,pr.cost,pr.price,pr.category_id,pr.subcategory_id,(select name from sma_product_variants where  id = {$this->db->dbprefix('order_items')}.option_id) as sizevarient,{$this->db->dbprefix('product_variants')}.name as varient,{$this->db->dbprefix('order_items')}.item_tax,{$this->db->dbprefix('order_items')}.item_discount,{$this->db->dbprefix('order_items')}.tax,{$this->db->dbprefix('order_items')}.unit_price,{$this->db->dbprefix('order_items')}.real_unit_price,{$this->db->dbprefix('order_items')}.invoice_total_net_unit_price,{$this->db->dbprefix('order_items')}.net_unit_price from {$this->db->dbprefix('order_items')} ";
        $si .= "LEFT JOIN {$this->db->dbprefix('product_variants')} ON {$this->db->dbprefix('order_items')}.option_id = {$this->db->dbprefix('product_variants')}.id";
        $si .= " LEFT JOIN  $pi ON {$this->db->dbprefix('order_items')}.product_id = pr.id  ) FSI";

        if ($pdf || $xls || $img) {


            $this->db->select("FSI.product_name as product_name,FSI.sizevarient as sizevarient,FSI.quantity as quantity,FSI.unit_price as unit_price, {$this->db->dbprefix('orders')}.id as id", FALSE)->from('orders')->join($si, 'FSI.sale_id=orders.id', 'left');
            // ->group_by('sales.id');

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->db->where('orders.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->db->where('orders.created_by', $user);
                    }
                }
            }

            if ($category) {
                $this->db->where('FSI.category_id', $category);
            }

            if ($subcategory) {
                $this->db->where('FSI.subcategory_id', $subcategory);
            }

            if ($ordertype) {
                $this->db->where('orders.sale_status', $ordertype);
            }

            if ($start_date) {
                $this->db->where('DATE(' . $this->db->dbprefix('orders') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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

                $this->excel->getActiveSheet()->getStyle("A1:P1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:P1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Order_Report');
                $this->excel->getActiveSheet()->setTitle(lang('Order_Report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('varient'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('total_price'));
                /*
                  $this->excel->getActiveSheet()->SetCellValue('D2', lang('product_name'));
                  $this->excel->getActiveSheet()->SetCellValue('E2', lang('Category'));
                  $this->excel->getActiveSheet()->SetCellValue('F2', lang('Sub_Category'));
                  $this->excel->getActiveSheet()->SetCellValue('G2', lang('Brand'));
                  $this->excel->getActiveSheet()->SetCellValue('H2', lang('style_code'));
                  $this->excel->getActiveSheet()->SetCellValue('I2', lang('Size'));
                  $this->excel->getActiveSheet()->SetCellValue('J2', lang('Cost'));
                  $this->excel->getActiveSheet()->SetCellValue('K2', lang('Selling_Price'));
                  $this->excel->getActiveSheet()->SetCellValue('L2', lang('Discount'));
                  $this->excel->getActiveSheet()->SetCellValue('M2', lang('GST %'));
                  $this->excel->getActiveSheet()->SetCellValue('N2', lang('GST'));
                  $this->excel->getActiveSheet()->SetCellValue('O2', lang('Gross_Profit'));
                  $this->excel->getActiveSheet()->SetCellValue('P2', lang('Net_Profit'));
                 */
                $row = 3;
                $totprice = 0;
                $proprice = 0;
                $sprice = 0;

                foreach ($data as $data_row) {

                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->product_name);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->sizevarient);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->quantity);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->unit_price);

                    $totprice += $data_row->unit_price;

                    $row++;
                }
                // $this->excel->getActiveSheet()->getStyle("C" . $row . ":G" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $totprice);


                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);

                $filename = 'Order_Details_report';
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/product_profit_and_loss.pdf', __FILE__));
                    redirect("reports/create_image/product_profit_and_loss.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');

            $this->datatables->select("FSI.product_name as product_name,FSI.sizevarient as sizevarient,FSI.quantity as quantity,FSI.unit_price as unit_price, {$this->db->dbprefix('orders')}.id as id", FALSE)->from('orders')->join($si, 'FSI.sale_id=orders.id', 'left');
            // ->group_by('sales.id');

            if ($this->Owner || $this->Admin) {
                if ($this->input->get('user')) {
                    $this->datatables->where('orders.created_by', $this->input->get('user'));
                }
            } else {
                if ($this->session->userdata('view_right') == '0') {
                    if ($user) {
                        $this->datatables->where('orders.created_by', $user);
                    }
                }
            }

            if ($category) {
                $this->datatables->where('FSI.category_id', $category);
            }

            if ($subcategory) {
                $this->datatables->where('FSI.subcategory_id', $subcategory);
            }

            if ($ordertype) {
                $this->datatables->where('orders.sale_status', $ordertype);
            }

            if ($start_date) {
                $this->datatables->where('DATE(' . $this->db->dbprefix('orders') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    /*     * * */

    public function sale_purchase_chart_details($warehouse_id = 0) {
        $this->data['wareget'] = $warehouse_id = $this->uri->segment(3);
        $this->data['ViewType'] = $this->uri->segment(4) ? $this->uri->segment(4) : 'sale';
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        $this->data['monthly_records'] = $this->reports_model->sale_purchase_chart_details($warehouse_id, 'Monthly');
        $this->data['daily_records'] = $this->reports_model->sale_purchase_chart_details($warehouse_id, 'Daily');
        $this->data['combine_monthly_records'] = $this->reports_model->sale_purchase_chart_details($warehouse_id, 'Monthly');
        $this->data['combine_daily_records'] = $this->reports_model->sale_purchase_chart_details($warehouse_id, 'Daily');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Sale Purchase Chart Details')));
        $meta = array('page_title' => lang('Sale Purchase Chart Details'), 'bc' => $bc);
        $this->page_construct('reports/sale_purchase_chart_details', $meta, $this->data);
    }

    function brand_chart_details($warehouse_id = 0) {
        $warehouse_id = $this->uri->segment(3);
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        $monthly_records = $this->reports_model->brand_chart_details($warehouse_id, 'Monthly');
        $daily_records = $this->reports_model->brand_chart_details($warehouse_id, 'Daily');
        for ($i = 0; $i < 6; $i++) {
            $months[] = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
        }
        for ($i = 0; $i < 7; $i++) {
            $daily[] = date('d-m-Y', strtotime("-$i days"));
        }
        $this->data['months'] = $months;
        $this->data['daily'] = $daily;
        $this->data['monthly_arr'] = $monthly_records;
        $this->data['daily_arr'] = $daily_records;
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Brand Chart Details')));
        $meta = array('page_title' => lang('Brand Chart Details'), 'bc' => $bc);
        $this->page_construct('reports/brand_chart_details', $meta, $this->data);
    }

    function categories_chart_details($warehouse_id = 0) {
        $warehouse_id = $this->uri->segment(3);
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        $monthly_records = $this->reports_model->sale_categories_chart_details($warehouse_id, 'Monthly');
        //echo '<pre>';
        //echo print_r($monthly_records);
        //exit;
        $daily_records = $this->reports_model->sale_categories_chart_details($warehouse_id, 'Daily');
        for ($i = 0; $i < 6; $i++) {
            $months[] = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
        }
        for ($i = 0; $i < 7; $i++) {
            $daily[] = date('d-m-Y', strtotime("-$i days"));
        }
        $this->data['months'] = $months;
        $this->data['daily'] = $daily;
        $this->data['monthly_arr'] = $monthly_records;
        $this->data['daily_arr'] = $daily_records;
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Categories Chart Details')));
        $meta = array('page_title' => lang('Categories Chart Details'), 'bc' => $bc);
        $this->page_construct('reports/categories_chart_details', $meta, $this->data);
    }

    function categories_brand_chart_details($warehouse_id = 0) {
        $this->data['warehouse_id'] = $warehouse_id = $this->uri->segment(3);
        $this->data['selected_start_date'] = $StartDate = $this->uri->segment(4) ? $this->uri->segment(4) : date('d-m-Y');
        $this->data['selected_end_date'] = $EndDate = $this->uri->segment(5) ? $this->uri->segment(5) : date('d-m-Y');
        $this->data['FilterBy'] = $FilterBy = $this->uri->segment(6) ? $this->uri->segment(6) : 'category';
        $this->data['Records'] = $Records = $this->uri->segment(7) ? $this->uri->segment(7) : 'All';
        $this->data['Sale_Purchase'] = $Sale_Purchase = $this->uri->segment(8) ? $this->uri->segment(8) : 'Sale';
        $this->data['cat_id'] = $cat_id = $this->uri->segment(9) ? $this->uri->segment(9) : '';

        $this->data['show_start_date'] = $db_start_date = $this->sma->fld($StartDate);
        $this->data['show_end_date'] = $db_end_date = $this->sma->fld($EndDate);
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        if ($FilterBy == 'category') {
            if ($Sale_Purchase == 'Sale') {
                $categories_records = $this->reports_model->sale_categories_chart_details($warehouse_id, $db_start_date, $db_end_date, $cat_id, $Records);
                $this->data['categories_arr'] = $categories_records;
                $categories_records1 = $this->reports_model->sale_categories_chart_details($warehouse_id, $db_start_date, $db_end_date, $cat_id, $Records);
                $this->data['categories_list_arr'] = $categories_records1;
            } else {
                $categories_records = $this->reports_model->purchase_categories_chart_details($warehouse_id, $db_start_date, $db_end_date, $cat_id, $Records);
                $this->data['categories_arr'] = $categories_records;
                $categories_records1 = $this->reports_model->purchase_categories_chart_details($warehouse_id, $db_start_date, $db_end_date, $cat_id, $Records);
                $this->data['categories_list_arr'] = $categories_records1;
            }
        } else {
            if ($Sale_Purchase == 'Sale') {
                $brand_records = $this->reports_model->sale_brand_chart_details($warehouse_id, $db_start_date, $db_end_date, $Records);
                $this->data['brand_arr'] = $brand_records;
            } else {
                $brand_records = $this->reports_model->purchase_brand_chart_details($warehouse_id, $db_start_date, $db_end_date, $Records);
                $this->data['brand_arr'] = $brand_records;
            }
        }

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Categories & Brand Chart Details')));
        $meta = array('page_title' => lang('Categories & Brand Chart Details'), 'bc' => $bc);
        $this->page_construct('reports/categories_brand_chart_details', $meta, $this->data);
    }

    function payment_chart_details($warehouse_id = 0) {
        $this->data['warehouse_id'] = $warehouse_id = $this->uri->segment(3);
        $this->data['selected_start_date'] = $StartDate = $this->uri->segment(4) ? $this->uri->segment(4) : date('d-m-Y');
        $this->data['selected_end_date'] = $EndDate = $this->uri->segment(5) ? $this->uri->segment(5) : date('d-m-Y');
        $this->data['Records'] = $Records = $this->uri->segment(6) ? $this->uri->segment(6) : 'All';
        $this->data['Sale_Purchase'] = $Sale_Purchase = $this->uri->segment(7) ? $this->uri->segment(7) : 'Sale';

        $this->data['show_start_date'] = $db_start_date = $this->sma->fld($StartDate);
        $this->data['show_end_date'] = $db_end_date = $this->sma->fld($EndDate);
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        $payment_records = $this->reports_model->sale_purchase_payment_summary_chart($warehouse_id, $db_start_date, $db_end_date, $Records, $Sale_Purchase);
        $FinalPayment = array();
        foreach ($payment_records as $key => $val) {
            if (isset($this->pos_settings->award_point) && ($this->pos_settings->award_point == '0' && $val->paid_by == 'award_point') || ($this->pos_settings->UPI_QRCODE == '0' && $val->paid_by == 'UPI_QRCODE') || ($this->pos_settings->neft == '0' && $val->paid_by == 'NEFT') || ($this->pos_settings->gift_card == '0' && $val->paid_by == 'gift_card') || ($this->pos_settings->debit_card == '0' && $val->paid_by == 'DC') || ($this->pos_settings->credit_card == '0' && $val->paid_by == 'CC') || ($this->pos_settings->paytm_opt == '0' && $val->paid_by == 'PAYTM') || ($this->pos_settings->google_pay == '0' && $val->paid_by == 'Googlepay') || ($this->pos_settings->swiggy == '0' && $val->paid_by == 'swiggy') || ($this->pos_settings->zomato == '0' && $val->paid_by == 'zomato') || ($this->pos_settings->ubereats == '0' && $val->paid_by == 'ubereats') || ($this->pos_settings->magicpin == '0' && $val->paid_by == 'magicpin') || ($this->pos_settings->complimentry == '0' && $val->paid_by == 'complimentry')) {
                
            } else {
                $FinalPayment[] = $val;
            }
        }

        //print_r($payment_records); exit;
        $this->data['payment_arr'] = $FinalPayment;

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Payment Chart Details')));
        $meta = array('page_title' => lang('Payment Chart Details'), 'bc' => $bc);
        $this->page_construct('reports/payment_chart_details', $meta, $this->data);
    }

    function product_varient_purchase_report() {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->data['brands'] = $this->site->getAllBrands();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['max_varient_count'] = $this->reports_model->max_varient_count('Variant');
        $this->data['varient_name'] = $this->reports_model->getVarientName('Variant');

        $Data['category'] = $this->input->get('category') ? $this->input->get('category') : NULL;
        $Data['brand'] = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $Data['warehouse'] = $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $Data['Type'] = 'Variant';
        $this->data['limit_product_varient'] = $this->reports_model->count_product_varient_purchase_data($Data);

        $this->data['simple_datatable'] = 'simple_datatable';
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Products_Varient_Sale_Report')));
        $meta = array('page_title' => lang('Products_Varient_Purchase_Report'), 'bc' => $bc);
        $this->page_construct('reports/product_varient_purchase_report', $meta, $this->data);
    }

    function load_product_varient_purchase_report() {
        $Data['Type'] = 'Variant';
        $Data['category'] = $this->input->get('category') ? $this->input->get('category') : NULL;
        $Data['brand'] = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $Data['warehouse'] = $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $Data['v'] = $this->input->get('v') ? $this->input->get('v') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        if ($start_date) {
            $Data['start_date'] = $start_date = $this->sma->fld($start_date);
            $Data['end_date'] = $end_date = $this->sma->fld($end_date);
        }
        if ($Data['v'] == 'export') {
            $strtlimit = $this->input->get('strtlimit') ? $this->input->get('strtlimit') : NULL;
            $limt = explode("-", $strtlimit);
            if ($limt) {
                $Data['start'] = $start = $limt[0];
                $Data['limit'] = $limit = $limt[1];
            }
            //echo $start.' '.$limit; exit;
        }

        //$Query = "select p.name, p.code, c.name as cat_name, b.name as brand_name, p.quantity as qty, (p.quantity * p.cost) as product_cost from sma_products p inner join sma_categories c on p.category_id=c.id inner join sma_brands b on b.id=p.brand inner join sma_warehouses_products_variants wpv on p.id=wpv.product_id group by wpv.product_id ";

        $total = $this->reports_model->count_product_varient_purchase_data($Data, isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
        $data = array();
        $Result = $this->reports_model->load_product_varient_purchase_data($Data, isset($_REQUEST['start']) ? $_REQUEST['start'] : "", isset($_REQUEST['length']) ? $_REQUEST['length'] : "", isset($_REQUEST['search']) ? $_REQUEST['search'] : "");
        $dataArray = array();
        if ($Result->num_rows() > 0) {
            $Res = $Result->result_array();
            foreach ($Res as $key => $value) {
                $ProductId = $value['product_id'];
                $row_data['product_id'] = $value['product_id'];
                $row_data['name'] = $value['name'];
                $row_data['code'] = $value['code'];
                $row_data['cat_name'] = $value['cat_name'];
                $row_data['brand_name'] = $value['brand_name'];

                $i = 1;
                $VarientName = $this->reports_model->getVarientName('Variant');
                $TotalQty = 0;
                if (!empty($VarientName)) {
                    foreach ($VarientName as $key_varient_name => $value_varient_name) {
                        $Sql = "select pv.product_id, p.name as p_name, sum(ssi.subtotal) as varient_cost, pv.name, pv.cost, pv.price, sum(ssi.quantity) as quantity from sma_products p inner join sma_product_variants pv on p.id=pv.product_id inner join sma_purchase_items ssi on pv.id=ssi.option_id inner join sma_purchases s on s.id=ssi.purchase_id where p.id='$ProductId' and pv.name='" . $value_varient_name['name'] . "'";

                        if ($warehouse) {
                            $Sql .= " and ssi.warehouse_id='$warehouse' ";
                        }
                        if ($Data['start_date']) {
                            $Sql .= " and DATE(s.date) BETWEEN '" . $Data['start_date'] . "' and '" . $Data['end_date'] . "'";
                        }
                        $Sql .= " group by ssi.option_id ";
                        $Res = $this->db->query($Sql);
                        $Row = $Res->result_array();
                        if (!empty($Row)) {
                            foreach ($Row as $varient_key => $varient_value) {
                                $row_data['v_' . $value_varient_name['id']] = $this->sma->formatQuantity($varient_value['quantity']);
                                $TotalQty += $varient_value['quantity'];
                            }
                        } else {
                            $row_data['v_' . $value_varient_name['id']] = '';
                        }
                    }
                }
                $row_data['qty_product_cost'] = $this->sma->formatQuantity($TotalQty);
                $dataArray[] = $row_data;
            }
        }
        if ($Data['v'] == 'export') {
            $segment = '';
            if ($this->uri->segment(3))
                $segment = $this->uri->segment(3);
            //exit;
            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

            $this->excel->getActiveSheet()->getStyle("A1:AZ1")->applyFromArray($style);
            $this->excel->getActiveSheet()->mergeCells('A1:AZ1');
            $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Varient Item Report');
            $this->excel->getActiveSheet()->setTitle(lang('products_report'));
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('Product Name'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Code'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Category'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Brand'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Qty'));
            $VariantLength = count($VarientName);
            $SequenceLength = $VariantLength + 5;
            $Sequence = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            $ArraySequence = array();
            $length = 1;
            $len = 0;
            $len_inr = 0;
            for ($i = 0; $i <= $SequenceLength - 1; $i++) {
                if ($i > 25)
                    $ArraySequence[] = $Sequence[$len] . $Sequence[$len_inr];
                else
                    $ArraySequence[] = $Sequence[$i];

                //echo '<br>'.$length.' '.$i.' '.$len;
                $length++;
                $len_inr++;
                if ($length == 27) {
                    $length = 1;
                    $len_inr = 0;
                    if ($i > 25) {
                        $len++;
                    }
                }
            }
            $SequenceInr = 5;
            if (!empty($VarientName)) {
                foreach ($VarientName as $key_varient_name => $value_varient_name) {
                    $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . '2', $value_varient_name['name']);
                    $SequenceInr++;
                }
            }
            $row = 3;
            $pcost = 0;
            $pQty = 0;
            $sQty = 0;
            $scost = 0;
            $prAmt = 0;
            $stqty = 0;
            $ststock = 0;
            foreach ($dataArray as $keys => $data_row) {
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row['name']);
                $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row['code']);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row['cat_name']);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row['brand_name']);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row['qty_product_cost']);
                $SequenceInr = 5;
                if (!empty($VarientName)) {
                    foreach ($VarientName as $key_varient_name => $value_varient_name) {
                        $this->excel->getActiveSheet()->SetCellValue($ArraySequence[$SequenceInr] . $row, $data_row['v_' . $value_varient_name['id']]);
                        $SequenceInr++;
                    }
                }
                $row++;
            }
            //  $this->excel->getActiveSheet()->getStyle("C" . $row . ":H" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
            $filename = 'purchase_products_varient_item_report';
            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            if ($segment == 'pdf') {
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
            if ($segment == 'xls') {
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
            if ($segment == 'img') {
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
                $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/purchase_products_varient_item_report.pdf', __FILE__));
                redirect("reports/create_image/purchase_products_varient_item_report.pdf");
                exit();
            }
        } else {
            $DataArray = array('draw' => $_REQUEST['draw'], 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $dataArray);
            echo json_encode($DataArray);
        }
    }

    function getCatData() {
        $CatName = $this->input->get('cat_name');
        $Arr = $this->reports_model->getCatByName($CatName);
        echo json_encode($Arr);
    }

    /*     * ********************************************* */

    function get_customer_wise_sales() {
        //$this->sma->checkPermissions('sales');
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();
        $this->data['salecount'] = $this->getCountSales();
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['billers'] = $this->site->getAllCompanies('biller');
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Customer_wise_Sale_Report')));
        $meta = array('page_title' => lang('Customer_wise_Sale_Report'), 'bc' => $bc);
        $this->page_construct('reports/customer_wise_sales', $meta, $this->data);
    }

    function getCustomerSalesReport($pdf = NULL, $xls = NULL, $img = NULL) {
        //$this->sma->checkPermissions('sales', TRUE);
        $start = '';
        $limit = '';

        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $biller = $this->input->get('biller') ? $this->input->get('biller') : NULL;
        $warehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $strtlimit = $this->input->get('strtlimit') ? $this->input->get('strtlimit') : NULL;
        $FilterSaleType = $this->input->get('filter_sale_type') ? $this->input->get('filter_sale_type') : NULL;
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
            if ($FilterSaleType == 'No_sale') {
                $this->db->select('sma_sales.customer_id');
                if ($this->Owner || $this->Admin) {
                    if ($this->input->get('user')) {
                        $this->db->where('sales.created_by', $this->input->get('user'));
                    }
                } else {
                    if ($this->session->userdata('view_right') == '0') {
                        if ($user) {
                            $this->db->where('sales.created_by', $user);
                        }
                    }
                }

                if ($biller) {
                    $this->db->where('sales.biller_id', $biller);
                }
                if ($customer) {
                    $this->db->where('sales.customer_id', $customer);
                }
                if ($warehouse) {
                    $getwarehouse = str_replace("_", ",", $warehouse);
                    $this->db->where('sales.warehouse_id IN (' . $getwarehouse . ')');
                }
                if ($start_date) {
                    $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                }
                $this->db->group_by('sma_sales.customer_id');

                $q = $this->db->get('sales');
                $customer_arr = array();
                if ($q->num_rows() > 0) {
                    foreach (($q->result_array()) as $row) {
                        $customer_arr[] = $row['customer_id'];
                    }
                }
                //print_r($customer_arr);
                $this->db->select("id, name, email, phone", FALSE)->from('companies');
                if ($customer_arr) {

                    $customer_ids = implode("','", $customer_arr);
                    $this->db->where("id NOT IN ('" . $customer_ids . "')");
                }
                $this->db->where('group_id', 3);
                $q = $this->db->get();
            } else {
                $this->db->select("companies.name, companies.email, companies.phone, sum(sma_sales.grand_total+sma_sales.rounding) as grand_total, companies.id", FALSE)->from('companies')->join('sales', 'sales.customer_id=companies.id', 'inner')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');
                if ($this->Owner || $this->Admin) {
                    if ($this->input->get('user')) {
                        $this->db->where('sales.created_by', $this->input->get('user'));
                    }
                } else {
                    if ($this->session->userdata('view_right') == '0') {
                        if ($user) {
                            $this->db->where('sales.created_by', $user);
                        }
                    }
                }


                if ($FilterSaleType == 'Top_Sale')
                    $this->db->order_by('grand_total desc');
                else if ($FilterSaleType == 'Bottom_Sale')
                    $this->db->order_by('grand_total asc');


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

                if ($start_date) {
                    $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                }
                $this->db->group_by('sales.customer_id');
                if ($limit != '' && $start != '') {
                    $this->db->limit($limit, $start);
                }
                $q = $this->db->get();
            }

            $data_sales = [];
            $products = '';
            //echo '<pre>';
            if ($q->num_rows() > 0) {
                //print_r($q->result()); exit;
                foreach (($q->result()) as $row) {

                    $id = $row->id;
                    $data[$id]['customer_name'] = $row->name;
                    $data[$id]['customer_email'] = $row->email;
                    $data[$id]['customer_phone'] = $row->phone;
                    $data[$id]['grand_total'] = $row->grand_total;
                    //$data[] = $row;
                    //   print_r($data);exit;
                }//forloop
            } else {
                $data = NULL;
            }
            //print_r($data);exit;
            if (!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:D1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:D1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Customer wise Sales Report');
                $this->excel->getActiveSheet()->setTitle(lang('Customer wise Sales Report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Customer Name'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Email'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Phone'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('grand_total'));

                $row = 3;
                $total = 0;
                $paid = 0;
                $balance = 0;
                foreach ($data as $sale_id => $salesdata) {
                    $data_row = (object) $salesdata;
                    //echo $data_row->customer_name;
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->customer_name);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->customer_email);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->customer_phone);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->grand_total);

                    $total += $data_row->grand_total + $data_row->rounding;
                    $row++;
                }
                //print_r($data);
                //exit;
                $this->excel->getActiveSheet()->getStyle("D" . $row . ":D" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $total);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);

                $filename = 'customer_wise_sales_report';
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
            $this->load->library('datatables');
            if ($FilterSaleType == 'No_sale') {
                $this->db->select('sma_sales.customer_id');
                if ($this->Owner || $this->Admin) {
                    if ($this->input->get('user')) {
                        $this->db->where('sales.created_by', $this->input->get('user'));
                    }
                } else {
                    if ($this->session->userdata('view_right') == '0') {
                        if ($user) {
                            $this->db->where('sales.created_by', $user);
                        }
                    }
                }

                if ($biller) {
                    $this->db->where('sales.biller_id', $biller);
                }
                if ($customer) {
                    $this->db->where('sales.customer_id', $customer);
                }
                if ($warehouse) {
                    $getwarehouse = str_replace("_", ",", $warehouse);
                    $this->db->where('sales.warehouse_id IN (' . $getwarehouse . ')');
                }
                if ($start_date) {
                    $this->db->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                }
                $this->db->group_by('sma_sales.customer_id');

                $q = $this->db->get('sales');
                $customer_arr = array();
                if ($q->num_rows() > 0) {
                    foreach (($q->result_array()) as $row) {
                        $customer_arr[] = $row['customer_id'];
                    }
                }
                //print_r($customer_arr);
                $this->datatables->select("id, name, email, phone", FALSE)->from('companies');
                $this->datatables->add_column('grand_total', '00', '');
                if ($customer_arr) {

                    $customer_ids = implode("','", $customer_arr);
                    $this->datatables->where("id NOT IN ('" . $customer_ids . "')");
                }
                $this->datatables->where('group_id ', 3);
            } else {
                $this->datatables->select("{$this->db->dbprefix('companies')}.id as id, {$this->db->dbprefix('companies')}.name, {$this->db->dbprefix('companies')}.email, {$this->db->dbprefix('companies')}.phone, sum({$this->db->dbprefix('sales')}.grand_total+{$this->db->dbprefix('sales')}.rounding) as grand_total", FALSE)->from('companies')->join('sales', 'sales.customer_id=companies.id', 'inner')->join('warehouses', 'warehouses.id=sales.warehouse_id', 'inner')->group_by('sales.customer_id');
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
                if ($start_date) {
                    $this->datatables->where('DATE(' . $this->db->dbprefix('sales') . '.date) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
                }
                if ($FilterSaleType == 'Top_Sale') {
                    $this->datatables->order_by('grand_total desc');
                } else if ($FilterSaleType == 'Bottom_Sale') {
                    $this->datatables->order_by('grand_total asc');
                }
            }

            echo $this->datatables->generate();
        }
    }

    function user_modal_view($id = NULL) {
        // $this->sma->checkPermissions('index', TRUE);
        $this->db->select("DATE_FORMAT(created_at, '%Y-%m-%d %T') as date, action_comment, action_reff_id, action_type, user_name, action_url, id, action_affected_data", FALSE)->from('user_action_logs');
        $this->db->where('id', $id);
        $q = $this->db->get();
        $this->data['UserLogView'] = $q->result();
        $this->data['UserLogViewId'] = $id;
        $this->load->view($this->theme . 'reports/user_modal_view', $this->data);
    }

    public function user_log_action($id = NULL) {
        // $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getStaff();

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('User Action Log')));
        $meta = array('page_title' => lang('User Action Log'), 'bc' => $bc);
        $this->page_construct('reports/user_log_action', $meta, $this->data);
    }

    public function getUserLogActionReport($pdf = NULL, $xls = NULL, $img = NULL) {
        //$this->sma->checkPermissions('expenses');

        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $this->sma->fld($end_date);
        }

        if ($pdf || $xls || $img) {

            $this->db->select("DATE_FORMAT(created_at, '%Y-%m-%d %T') as date, action_comment, action_reff_id, action_type, user_name, action_url, id", FALSE)->from('user_action_logs');

            if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
                $this->db->where('user_id', $this->session->userdata('user_id'));
            }

            if ($user) {
                $this->db->where('user_id', $user);
            }
            if ($start_date) {
                $this->db->where('DATE(created_at) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
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
                $this->excel->getActiveSheet()->SetCellValue('A1', 'User_Log_Action_report');

                $this->excel->getActiveSheet()->setTitle(lang('User_Log_Action_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Module'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Action_Reference_Id'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Action'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('created_by'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Url'));
                $row = 3;
                $total = 0;
                foreach ($data as $data_row) {
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->action_comment);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->action_reff_id);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->action_type);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->user_name);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->action_url);
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

                $filename = 'user_log_action_report';
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
            $this->datatables->select("DATE_FORMAT(created_at, '%Y-%m-%d %T') as date, action_comment, action_reff_id, action_type, user_name, action_url, id", FALSE)->from('user_action_logs');

            if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
                $this->datatables->where('user_id', $this->session->userdata('user_id'));
            }
            if ($user) {
                $this->datatables->where('user_id', $user);
            }
            if ($start_date) {
                $this->datatables->where('DATE(created_at) BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }

            echo $this->datatables->generate();
        }
    }

    function products_transactions() {
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
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_transactions_report')));
        $meta = array('page_title' => lang('products_transactions_report'), 'bc' => $bc);
        $this->page_construct('reports/products_transactions', $meta, $this->data);
    }

    function getProductsTransactionsReport() {

        $this->sma->checkPermissions('products', TRUE);

        $product = isset($_REQUEST['product']) ? $_REQUEST['product'] : NULL;
        $category = isset($_REQUEST['category']) ? $_REQUEST['category'] : NULL;
        $brand = isset($_REQUEST['brand']) ? $_REQUEST['brand'] : NULL;
        $subcategory = isset($_REQUEST['subcategory']) ? $_REQUEST['subcategory'] : NULL;
        $warehouse = isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : NULL;

        $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : NULL;
        $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : NULL;
        $with_or_without_gst = isset($_REQUEST['with_or_without_gst']) ? $_REQUEST['with_or_without_gst'] : NULL;
        $purchase_date_filter = isset($_REQUEST['purchase_date_filter']) ? $_REQUEST['purchase_date_filter'] : NULL;

        $products_where = [];
        if (!empty($product)) {
            $products_where[] = " prd.`id`= '$product' ";
        } else {
            if (!empty($brand)) {
                $products_where[] = " prd.`brand` = '$brand' ";
            } else {
                if (!empty($category))
                    $products_where[] = " prd.`category_id` = '$category' ";
                if (!empty($subcategory))
                    $products_where[] = " prd.`subcategory_id` = '$subcategory' ";
            }
        }

        if (!empty($products_where)) {
            $product_subquery = "SELECT id FROM sma_products prd WHERE " . join(' AND ', $products_where);
            $sale_where[] = " s.product_id IN ($product_subquery) ";
            $purchase_where[] = " p.product_id IN ($product_subquery) ";
        }

        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $end_date ? $this->sma->fld($end_date) : date('Y-m-d');
            if ($purchase_date_filter) {
                $purchase_where[] = " ( p.transaction_date >= '{$start_date}' AND p.transaction_date < '{$end_date}' ) ";
            }
            $sale_where[] = " ( DATE(s.date) >= '{$start_date}' AND DATE(s.date) < '{$end_date}' ) ";
        }

        if ($warehouse) {
            $sale_where[] = " s.warehouse_id = '$warehouse' ";
            $purchase_where[] = " p.`warehouse_id` = '$warehouse' ";
        }

        $sale_where_conditions = $purchase_where_conditions = '';

        if (!empty($sale_where)) {
            $sale_count_where_conditions = ' (' . join(' AND ', $sale_where) . ') ';

            $sale_where_conditions = ' WHERE ' . join(' AND ', $sale_where);
        }

        if (!empty($purchase_where)) {
            $purchase_count_where_conditions = ' (' . join(' AND ', $purchase_where) . ') ';
            $purchase_where_conditions = ' AND ' . join(' AND ', $purchase_where);
        }

        $query_count = "SELECT COUNT( DISTINCT prd.id ) total_data "
                . "FROM sma_products prd "
                . "left join `sma_view_products_transactions` p on prd.id = p.`product_id` "
                . "left join `sma_view_products_sales` s on prd.id = s.`product_id` "
                . "WHERE p.purchase_item_status IN ('received', 'partial', 'returned') ";

        /* if (!empty($purchase_count_where_conditions) || !empty($sale_count_where_conditions)) {
          $query_count .= ' AND ' . $purchase_count_where_conditions . ' OR ' . $sale_count_where_conditions;
          } */


        if (!empty($purchase_count_where_conditions)) {
            $query_count .= ' AND ' . $purchase_count_where_conditions;
        }
        if (!empty($sale_count_where_conditions)) {
            $query_count .= ' AND ' . $sale_count_where_conditions;
        }


        $emptyPurchaseArr = array("purchase_qty" => 0, "adjustment_qty" => 0, "transfer_qty" => 0, "quantity_balance" => 0, "purchase_cost" => 0, "purchase_cost_include_tax" => 0, "balance_stock_value" => 0, "balance_stock_value_include_tax" => 0,);
        $emptySalesArr = array("sold_qty" => 0, "total_Sale_amount" => 0, "total_Sale_amount_include_tax" => 0,);
        $purchasesData = $salesData = [];
        $per_page_rows = $this->input->post('display_rows') ? $this->input->post('display_rows') : 15;
        $page = $this->input->post('page') ? $this->input->post('page') : 1;
        $offset = ($page - 1) * $per_page_rows;

        if (!isset($_GET['export'])) {
            $query_limit = " LIMIT $offset, $per_page_rows ";
        }
        $query_purchase_data = "SELECT prd.id AS product_id, prd.code AS product_code, prd.name AS product_name, 
                        SUM(p.`purchase_qty`) purchase_qty ,
                        SUM(p.`adjustment_qty`) adjustment_qty, 
                        SUM(p.`transfer_qty`) transfer_qty,
                        SUM(p.`quantity_balance`) quantity_balance,
                        SUM(IF( p.transfer_id IS NULL ,(p.`subtotal` - p.`item_tax`),0)) purchase_cost,
                        SUM(IF( p.transfer_id IS NULL ,p.`subtotal`,0)) purchase_cost_include_tax,
                        SUM(p.`quantity_balance` * p.`net_unit_cost` ) balance_stock_value,
                        SUM(p.`quantity_balance` * p.`unit_cost` ) balance_stock_value_include_tax                    
                    FROM `sma_products` prd 
                    LEFT JOIN `sma_view_products_transactions` p ON prd.id = p.product_id 
                    WHERE p.purchase_item_status IN ('received', 'partial', 'returned') 
                    $purchase_where_conditions  
                    GROUP BY prd.`id` ORDER BY prd.`id` $query_limit ";

        $query_sale_data = "SELECT prd.id AS product_id, prd.code AS product_code, prd.name AS product_name, 
                        SUM(s.quantity) AS sold_qty  ,
                        SUM(s.subtotal - s.item_tax ) AS total_Sale_amount , 
                        SUM(s.subtotal) AS total_Sale_amount_include_tax 
                    FROM `sma_products` prd 
                    LEFT JOIN `sma_view_products_sales` AS s ON s.`product_id` = prd.id                     
                    $sale_where_conditions   
                    GROUP BY s.`product_id` ORDER BY prd.`id` $query_limit ";


//        echo $query_count;
//        echo "<br/><br/>Purchess: ".$query_purchase_data;
//        echo "<br/><br/>Sales: ".$query_sale_data;

        $countData = $this->db->query($query_count)->row();

        $purchasesResult = $this->db->query($query_purchase_data)->result();
        if (is_array($purchasesResult)) {
            foreach ($purchasesResult as $row) {
                $purchasesData[$row->product_id] = $row;
            }
        }

        $salesResult = $this->db->query($query_sale_data)->result();
        if (is_array($salesResult)) {
            foreach ($salesResult as $row) {
                $salesData[$row->product_id] = $row;
            }
        }
//           echo "<br/><br/>TotalCount: ".$countData->total_data;
//           echo "<br/><br/>purchasesData : ".count($purchasesData); 
//           echo "<br/><br/>SalesData : ".count($salesData); 

        $transactionArr = array_merge($purchasesData, $salesData);

        foreach ($transactionArr as $data) {
            $purchase = isset($purchasesData[$data->product_id]) ? $purchasesData[$data->product_id] : $emptyPurchaseArr;
            $sale = isset($salesData[$data->product_id]) ? $salesData[$data->product_id] : $emptySalesArr;
            $transactionData[$data->product_id] = array_merge((array) $purchase, (array) $sale);
        }


        if (isset($_GET['export'])) {

            if (!empty($transactionData)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:I1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Transaction Report');
                $this->excel->getActiveSheet()->setTitle(lang('products_transaction_report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Purchased'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Adjustment'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Transfer'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('purchased_amount'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('purchased_amount_with_gst'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('sold'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('sold_amount'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('sold_amount_with_gst'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('stock_in_hand'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('balance_amount'));
                $this->excel->getActiveSheet()->SetCellValue('M2', lang('balance_amount_with_gst'));
                $this->excel->getActiveSheet()->SetCellValue('N2', lang('profit_loss'));
                $this->excel->getActiveSheet()->SetCellValue('O2', lang('profit_loss'));

                $row = 3;
                $purchase_qty = $adjustment_qty = $transfer_qty = $purchase_cost = $purchase_cost_include_tax = $sold_qty = 0;
                $total_Sale_amount = $total_Sale_amount_include_tax = $quantity_balance = $balance_stock_value = $balance_stock_value_include_tax = 0;
                $total_profit_loss = $total_profit_loss_gst = 0;

                foreach ($transactionData as $product_id => $data_row) {
                    $product_code = $data_row['product_code'];
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, "$product_code ");
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row['product_name']);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row['purchase_qty']);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row['adjustment_qty']);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row['transfer_qty']);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row['purchase_cost']);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row['purchase_cost_include_tax']);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row['sold_qty']);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row['total_Sale_amount']);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row['total_Sale_amount_include_tax']);
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $data_row['quantity_balance']);
                    $this->excel->getActiveSheet()->SetCellValue('L' . $row, $data_row['balance_stock_value']);
                    $this->excel->getActiveSheet()->SetCellValue('M' . $row, $data_row['balance_stock_value_include_tax']);

                    $profit_loss = ($data_row['total_Sale_amount'] + $data_row['balance_stock_value']) - $data_row['purchase_cost'];
                    $profit_loss_gst = ($data_row['total_Sale_amount_include_tax'] + $data_row['balance_stock_value_include_tax']) - $data_row['purchase_cost_include_tax'];

                    $this->excel->getActiveSheet()->SetCellValue('N' . $row, $profit_loss);
                    $this->excel->getActiveSheet()->SetCellValue('O' . $row, $profit_loss_gst);

                    $purchase_qty += $data_row['purchase_qty'];
                    $adjustment_qty += $data_row['adjustment_qty'];
                    $transfer_qty += $data_row['transfer_qty'];
                    $purchase_cost += $data_row['purchase_cost'];
                    $purchase_cost_include_tax += $data_row['purchase_cost_include_tax'];
                    $sold_qty += $data_row['sold_qty'];
                    $total_Sale_amount += $data_row['total_Sale_amount'];
                    $total_Sale_amount_include_tax += $data_row['total_Sale_amount_include_tax'];
                    $quantity_balance += $data_row['quantity_balance'];
                    $balance_stock_value += $data_row['balance_stock_value'];
                    $balance_stock_value_include_tax += $data_row['balance_stock_value_include_tax'];
                    $total_profit_loss += $profit_loss;
                    $total_profit_loss_gst += $profit_loss_gst;

                    $row++;
                }//end foreach

                $this->excel->getActiveSheet()->getStyle("C" . $row . ":O" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $purchase_qty);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $adjustment_qty);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $transfer_qty);
                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $purchase_cost);
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $purchase_cost_include_tax);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $sold_qty);
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $total_Sale_amount);
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $total_Sale_amount_include_tax);
                $this->excel->getActiveSheet()->SetCellValue('K' . $row, $quantity_balance);
                $this->excel->getActiveSheet()->SetCellValue('L' . $row, $balance_stock_value);
                $this->excel->getActiveSheet()->SetCellValue('M' . $row, $balance_stock_value_include_tax);
                $this->excel->getActiveSheet()->SetCellValue('N' . $row, $total_profit_loss);
                $this->excel->getActiveSheet()->SetCellValue('O' . $row, $total_profit_loss_gst);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('N')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('O')->setWidth(25);

                $filename = 'products_transaction_report';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($_GET['export'] == 'pdf') {
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
                if ($_GET['export'] == 'xls') {
                    $this->excel->getActiveSheet()->getStyle('C2:O' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($_GET['export'] == 'img') {
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_transaction_report.pdf', __FILE__));
                    redirect("reports/create_image/products_transaction_report.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            if (is_array($transactionData)) {

                $totalRows = $countData->total_data;
                $totalPages = ceil($totalRows / $per_page_rows);

                $pagignation = $this->sma->ajax_pagignations($totalPages, $page);

                $tableHeader = '<table class="table table-striped table-bordered table-condensed table-hover dfTable reports-table" style="margin-bottom:5px;">
                            <thead>
                             <tr class="active">                                                       
                                <th colspan="2">Products</th>                           
                                <th colspan="4">Purchases </th>                             
                                <th colspan="2">Sold</th>                            
                                <th colspan="2">Stocks In Hand</th>
                                <th>Profit & Loss</th>
                            </tr> 
                            <tr> 
                                <th>Products Code</th>
                                <th>Products Name</th>
                                <th>(A)<br/>Purch<br/>ase<br/>(Qty.)</th>
                                <th>(B)<br/>Adjust<br/>ment<br/>(Qty.)</th>                            
                                <th>(C)<br/>Trans<br/>fer<br/>(Qty.)</th>                            
                                <th>(A+B)<br/>Total Cost</th>
                                <th>Qty</th>
                                <th>Price</th>            
                                <th>Qty</th>
                                <th>Cost</th>
                                <th>(<i class="fa fa-inr"></i>)</th> 
                            </tr>
                            </thead>
                            <tbody>';

                $tableRow = '';
                foreach ($transactionData as $rowdata) {
                    $rowdata = (array) $rowdata;
                    $purchase_cost = ($with_or_without_gst == 'without_gst') ? $rowdata['purchase_cost'] : $rowdata['purchase_cost_include_tax'];
                    $stock_value = ($with_or_without_gst == 'without_gst') ? $rowdata['balance_stock_value'] : $rowdata['balance_stock_value_include_tax'];
                    $sold_value = ($with_or_without_gst == 'without_gst') ? $rowdata['total_Sale_amount'] : $rowdata['total_Sale_amount_include_tax'];
                    $profit_loss = ($sold_value + $stock_value) - $purchase_cost;

                    $class_profit_loss = $profit_loss >= 0 ? 'text-default' : 'text-danger';

                    $row = '<tr><!--<td>' . $rowdata['product_id'] . '</td>--><td>' . $rowdata['product_code'] . '</td>';
                    $row .= '<td>' . $rowdata['product_name'] . '</td>';
                    $row .= '<td class="text-bold">' . number_format($rowdata['purchase_qty'], 0) . '</td>';
                    $row .= '<td>' . number_format($rowdata['adjustment_qty'], 0) . '</td>';
                    $row .= '<td>' . number_format($rowdata['transfer_qty'], 0) . '</td>';
                    $row .= '<td><i class="fa fa-inr"></i>&nbsp;' . number_format($purchase_cost, 2) . '</td>';
                    $row .= '<td class="text-success">' . number_format($rowdata['sold_qty'], 0) . '</td>';
                    $row .= '<td class="text-success"><i class="fa fa-inr"></i>&nbsp;' . number_format($sold_value, 2) . '</td>';
                    $row .= '<td class="text-primary">' . number_format($rowdata['quantity_balance'], 0) . '</td>';
                    $row .= '<td class="text-primary"><i class="fa fa-inr"></i>&nbsp;' . number_format($stock_value, 2) . '</td>';
                    $row .= '<td class="' . $class_profit_loss . '"><i class="fa fa-inr"></i>&nbsp;' . number_format($profit_loss, 2) . '</td>';
                    $row .= '</tr>';

                    $tableRow .= $row;
                }//end foreach.

                $tableFooter = '</tbody>
                                <tfoot class="dtFilter">
                                <tr class="active">                                 
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>                                 
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                </tfoot>
                            </table>';
            }

            echo $pagignation . $tableHeader . $tableRow . $tableFooter . $pagignation;
        }//end else
    }

    function products_ledgers() {
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
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_ledgers')));
        $meta = array('page_title' => lang('products_ledgers'), 'bc' => $bc);
        $this->page_construct('reports/products_ledgers', $meta, $this->data);
    }

    function getProductsLedgers() {

        $this->sma->checkPermissions('products', TRUE);

        $product = isset($_REQUEST['product']) ? $_REQUEST['product'] : NULL;
        $category = isset($_REQUEST['category']) ? $_REQUEST['category'] : NULL;
        $brand = isset($_REQUEST['brand']) ? $_REQUEST['brand'] : NULL;
        $subcategory = isset($_REQUEST['subcategory']) ? $_REQUEST['subcategory'] : NULL;
        $warehouse = isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : NULL;

        $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : NULL;
        $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : NULL;

        $products_where = [];
        if (!empty($product)) {
            $products_where[] = " `id`= '$product' ";
        } else {
            if (!empty($brand)) {
                $products_where[] = " `brand` = '$brand' ";
            } else {
                if (!empty($category))
                    $products_where[] = " `category_id` = '$category' ";
                if (!empty($subcategory))
                    $products_where[] = " `subcategory_id` = '$subcategory' ";
            }
        }

        if (!empty($products_where)) {
            $product_subquery = "SELECT id FROM sma_products  WHERE " . join(' AND ', $products_where);

            $purchase_where[] = "product_id IN ($product_subquery) ";
        }

        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = $end_date ? $this->sma->fld($end_date) : date('Y-m-d');
            $purchase_where[] = " ( date >= '{$start_date}' AND date < '{$end_date}' ) ";
        }

        if ($warehouse) {
            $purchase_where[] = " `warehouse_id` = '$warehouse' ";
        }

        $where_conditions = '';

        if (!empty($purchase_where)) {
            $where_conditions = " WHERE " . join(' AND ', $purchase_where);
        }

        $query_count = "SELECT COUNT( transaction_id ) total_data "
                . "FROM `sma_view_products_ledgers`"
                . $where_conditions;


        $query_data = "SELECT pl.*, w.code warehouse_code, w.name warehouse_name FROM `sma_view_products_ledgers` pl LEFT JOIN `sma_warehouses` w ON  pl.warehouse_id = w.id               
                    $where_conditions  
                    ORDER BY date, product_id $query_limit ";

        if (!isset($_GET['export'])) {

            $per_page_rows = $this->input->post('display_rows') ? $this->input->post('display_rows') : 30;
            $page = $this->input->post('page') ? $this->input->post('page') : 1;
            $offset = ($page - 1) * $per_page_rows;

            $query_data .= " LIMIT $offset, $per_page_rows ";
        }


//         echo $query_count;
//         echo "<br/><br/>".$query_data;


        $countData = $this->db->query($query_count)->row();

        $ledgerData = $this->db->query($query_data)->result();

        if (count($ledgerData)) {
            foreach ($ledgerData as $data) {
                $data->action = $data->purchase;
                switch ($data->action) {
                    case 'purchase':
                        $data->cr = $data->status == 'ordered' ? 0 : $data->quantity;
                        $data->dr = 0;
                        break;
                    case 'adjustment':
                        $data->cr = $data->quantity > 0 ? $data->quantity : 0;
                        $data->dr = $data->quantity < 0 ? (-1) * $data->quantity : 0;
                        break;
                    case 'transfer':
                        $data->cr = 0;
                        $data->dr = 0;
                        break;
                    case 'sale':
                        $data->cr = $data->quantity < 0 ? (-1) * $data->quantity : 0;
                        $data->dr = $data->quantity > 0 ? $data->quantity : 0;
                        break;
                }
                unset($data->purchase);
                $transactionData[] = (array) $data;
            }
        }

//           echo "<br/><br/>TotalCount: ".$countData->total_data;
//           echo "<br/><br/>ledgerData : ".count($ledgerData);  
//           
//           echo "<pre>";
//           print_r($transactionData);
//           echo "</pre>";

        if (isset($_GET['export'])) {

            if (!empty($transactionData)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:Q1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:Q1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Products Transaction Ledgers');
                $this->excel->getActiveSheet()->setTitle(lang('products_ledgers'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Transaction_Item_Id'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('transaction_id'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Transaction Type'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Product_Id'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('Option_Id'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Transaction_Quantity'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Quantity_Add (CR)'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('Quantity_Less (DR)'));
                $this->excel->getActiveSheet()->SetCellValue('L2', lang('Warehouse_Id'));
                $this->excel->getActiveSheet()->SetCellValue('M2', lang('Warehouse_Code'));
                $this->excel->getActiveSheet()->SetCellValue('N2', lang('Warehouse_Name'));
                $this->excel->getActiveSheet()->SetCellValue('O2', lang('stock_in_hand'));
                $this->excel->getActiveSheet()->SetCellValue('P2', lang('Transaction_Status'));
                $this->excel->getActiveSheet()->SetCellValue('Q2', lang('User_Id'));

                $row = 3;
                $total_qty = $cr_qty = $dr_qty = $in_stock_qty = 0;

                foreach ($transactionData as $data_row) {
                    $product_code = $data_row['product_code'];

                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row['transaction_id']);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row['action_id']);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row['action']);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row['date']);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row['product_id']);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row['product_code']);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row['product_name']);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row['option_id']);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row['quantity']);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row['cr']);
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $data_row['dr']);
                    $this->excel->getActiveSheet()->SetCellValue('L' . $row, $data_row['warehouse_id']);
                    $this->excel->getActiveSheet()->SetCellValue('M' . $row, $data_row['warehouse_code']);
                    $this->excel->getActiveSheet()->SetCellValue('N' . $row, $data_row['warehouse_name']);
                    $this->excel->getActiveSheet()->SetCellValue('O' . $row, $data_row['in_stock']);
                    $this->excel->getActiveSheet()->SetCellValue('P' . $row, $data_row['status']);
                    $this->excel->getActiveSheet()->SetCellValue('Q' . $row, $data_row['created_by']);

                    $total_qty += $data_row['quantity'];
                    $cr_qty += $data_row['cr'];
                    $dr_qty += $data_row['dr'];
                    $in_stock_qty += ($data_row['action'] == 'purchase' && $data_row['status'] == "ordered") ? 0 : $data_row['in_stock'];

                    $row++;
                }//end foreach

                $this->excel->getActiveSheet()->getStyle("C" . $row . ":Q" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('I' . $row, $total_qty);
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $cr_qty);
                $this->excel->getActiveSheet()->SetCellValue('K' . $row, $dr_qty);
                $this->excel->getActiveSheet()->SetCellValue('O' . $row, $in_stock_qty);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(50);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('N')->setWidth(40);
                $this->excel->getActiveSheet()->getColumnDimension('O')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('P')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('Q')->setWidth(15);

                $filename = 'products_ledgers';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($_GET['export'] == 'pdf') {
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
                if ($_GET['export'] == 'xls') {
                    $this->excel->getActiveSheet()->getStyle('C2:O' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($_GET['export'] == 'img') {
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_ledgers.pdf', __FILE__));
                    redirect("reports/create_image/products_ledgers.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            if (is_array($transactionData)) {

                $totalRows = $countData->total_data;
                $totalPages = ceil($totalRows / $per_page_rows);

                $pagignation = $this->sma->ajax_pagignations($totalPages, $page);

                $tableHeader = '<table class="table table-striped table-bordered table-condensed table-hover dfTable reports-table" style="margin-bottom:5px;">
                            <thead>                               
                            <tr> 
                                <th>Date</th>
                                <th>Products Code</th>
                                <th>Products Name</th>
                                <th>Action</th>
                                <th>Quantity</th>
                                <th>Qty. CR</th>
                                <th>Qty. DR</th>
                                <th>Warehouse</th>
                                <th>In Stock</th>
                                <th>Status</th>
                                <th>User</th>                                 
                            </tr>
                            </thead>
                            <tbody>';

                $tableRow = '';
                foreach ($transactionData as $rowdata) {
                    $rowdata = (array) $rowdata;

                    $class_qty_color = $rowdata['quantity'] > 0 ? 'text-default' : 'text-danger';

                    $row = '<tr><td>' . $rowdata['date'] . '</td>';
                    $row .= '<td>' . $rowdata['product_code'] . '</td>';
                    $row .= '<td>' . $rowdata['product_name'] . '</td>';
                    $row .= '<td>' . $rowdata['action'] . '</td>';
                    $row .= '<td class="$class_qty_color">' . number_format($rowdata['quantity'], 0) . '</td>';
                    $row .= '<td>' . number_format($rowdata['cr'], 0) . '</td>';
                    $row .= '<td>' . number_format($rowdata['dr'], 0) . '</td>';
                    $row .= '<td>' . $rowdata['warehouse_code'] . '</td>';

                    if ($rowdata['action'] == 'purchase' && $rowdata['status'] == "ordered") {
                        $row .= '<td class="text-danger"><del>' . number_format($rowdata['in_stock'], 0) . '</del></td>';
                    } else {
                        $row .= '<td>' . $balance = number_format($rowdata['in_stock'], 0) . '</td>';
                    }
                    $row .= '<td>' . $rowdata['status'] . '</td>';
                    $row .= '<td>' . $rowdata['created_by'] . '</td>';
                    $row .= '</tr>';

                    $tableRow .= $row;

                    $qty_Total += $rowdata['quantity'];
                    $cr_Total += $rowdata['cr'];
                    $dr_Total += $rowdata['dr'];
                    $instock_Total += $balance;
                }//end foreach.

                $tableFooter = '</tbody>
                                <tfoot class="dtFilter">
                                <tr>                                 
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>' . $qty_Total . '</th>
                                    <th>' . $cr_Total . '</th>
                                    <th>' . $dr_Total . '</th>
                                    <th></th>                                 
                                    <th>' . $instock_Total . '</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                </tfoot>
                            </table>';
            }

            echo $pagignation . $tableHeader . $tableRow . $tableFooter . $pagignation;
        }//end else
    }

    public function load_ajax_reports() {

        $postData = $_POST;
        switch ($_POST['action']) {
            case "ProductsTransactionsReport":
                $this->getProductsTransactionsReport($postData);

                break;

            case "ProductsLedgers":
                $this->getProductsLedgers($postData);

                break;

            case "CustomerLedgers":
                $this->getCustomerLedger($postData);

                break;

            case "CustomerDepositLadger":
                $this->getCustomerDepositLedger($postData);
                break;
            default:
                break;
        }
    }

    /**
     * Transfer Request Reports
     */
    public function transfer_request() {
        $this->sma->checkPermissions();
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['categories'] = $this->site->getAllCategories();

        $fwarehouse = $this->input->post('warehouse') ? $this->input->post('warehouse') : NULL;

        if ($fwarehouse) {
            $warehouses = $this->site->getWarehouseByID($fwarehouse);
        } else {
            $warehouses = $this->site->getAllWarehouses();
        }

        $this->data['warehouses'] = $warehouses;
        $this->data['brands'] = $this->site->getAllBrands();
        $this->data['users'] = $this->reports_model->getStaff();

        //$this->data['warehouses'] = $this->site->getAllWarehouses(); 
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Transfer_Request_Report')));
        $meta = array('page_title' => lang('Transfer Request'), 'bc' => $bc);
        $this->page_construct('reports/transfer_request', $meta, $this->data);
    }

    /**
     * Get Transfer Request Reports
     * @param type $pdf
     * @param type $xls
     * @param type $img
     */
    function gettransfer_request($pdf = NULL, $xls = NULL, $img = NULL) {
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $category = $this->input->get('category') ? $this->input->get('category') : NULL;
        $subcategory = $this->input->get('subcategory') ? $this->input->get('subcategory') : NULL;
        $brand = $this->input->get('brand') ? $this->input->get('brand') : NULL;
        $fwarehouse = $this->input->get('warehouse') ? $this->input->get('warehouse') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        if ($fwarehouse) {
            $warehouses = $this->reports_model->getWarehouse($fwarehouse);
        } else {
            $warehouses = $this->site->getAllWarehouses();
        }



        if ($pdf || $xls || $img) {


            // $this->db->select('sma_products.name as product_name,sma_product_variants.name  as varient,sma_categories.name,scategory.name as subcategory, ROUND(SUM(sma_transfer_request_items.request_quantity),2) as quantity, sma_transfer_request_items.product_id  ');
            $this->db->select('sma_transfer_request_items.product_name,sma_product_variants.name  as varient,sma_categories.name,scategory.name as subcategory, sma_brands.name as brand, ROUND(SUM(sma_transfer_request_items.request_quantity),2) as quantity, sma_transfer_request_items.product_id  ');

            $this->db->Join('sma_transfer_request', 'sma_transfer_request_items.transfer_request_id = sma_transfer_request.id ', 'rigth');
            $this->db->Join('sma_product_variants', 'sma_product_variants.id = sma_transfer_request_items.option_id ', 'LEFT');
            $this->db->Join('sma_products', 'sma_products.id = sma_transfer_request_items.product_id', 'LEFT');
            $this->db->Join('sma_categories', 'sma_categories.id = sma_products.category_id', 'LEFT');
            $this->db->Join('sma_categories as scategory', 'scategory.id = sma_products.subcategory_id', 'LEFT');

            $this->db->Join('sma_brands', 'sma_brands.id = sma_products.brand', 'LEFT');

            if ($product) {
                $this->db->where('sma_products.id', $product);
            }

            if ($category) {
                $this->db->where('sma_categories.id', $category);
            }

            if ($subcategory) {
                $this->db->where('scategory.id', $subcategory);
            }

            if ($brand) {
                $this->db->where('sma_brands.id', $brand);
            }

            if ($user) {
                $this->db->where('sma_transfer_request.created_by', $user);
            }


            if ($fwarehouse) {
                $this->db->where('sma_transfer_request_items.warehouse_id', $fwarehouse);
            }



            $this->db->where_in('sma_transfer_request.status', 'pending');
            $this->db->group_by('sma_transfer_request_items.product_id');
            $this->db->order_by('sma_products.name', 'ASC');
            $data = $this->db->get('sma_transfer_request_items')->result();


            if (!empty($data)) {
                $col = array('G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:I1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Transfer Request Report');
                $this->excel->getActiveSheet()->setTitle(lang('Transfer_Request_Report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Product Name'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Varient'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Category'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Subcategory'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Brand'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Total Request QTY'));
                foreach ($warehouses as $key => $wharehousename) {
                    $this->excel->getActiveSheet()->SetCellValue($col[$key] . '2', $wharehousename->name);
                }


                $row = 3;

                foreach ($data as $data_row) {

                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->product_name);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->varient);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->subcategory);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->brand);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->quantity);

                    foreach ($warehouses as $key => $wharehousename) {
                        $qty = $this->reports_model->warehouseqty($data_row->product_id, $wharehousename->id);
                        $this->excel->getActiveSheet()->SetCellValue($col[$key] . $row, $qty);
                    }


                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
                foreach ($warehouses as $key => $wharehousename) {
                    $this->excel->getActiveSheet()->getColumnDimension($col[$key])->setWidth(15);
                }

                $filename = 'Transfer_reports';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                /* echo $filename;
                  exit; */
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


            $this->load->library('datatables');
            $this->load->helper('reports');
            $this->datatables->select('sma_products.name as product_name,sma_product_variants.name  as varient,sma_categories.name,scategory.name as subcategory,sma_brands.name as brand, ROUND(SUM(sma_transfer_request_items.request_quantity),2) as quantity, sma_transfer_request_items.product_id  ');

            $this->datatables->from('sma_transfer_request_items');

            $this->datatables->Join('sma_transfer_request', 'sma_transfer_request_items.transfer_request_id = sma_transfer_request.id ', 'rigth');
            $this->datatables->Join('sma_product_variants', 'sma_product_variants.id = sma_transfer_request_items.option_id ', 'LEFT');
            $this->datatables->Join('sma_products', 'sma_products.id = sma_transfer_request_items.product_id', 'LEFT');
            $this->datatables->Join('sma_categories', 'sma_categories.id = sma_products.category_id', 'LEFT');
            $this->datatables->Join('sma_categories as scategory', 'scategory.id = sma_products.subcategory_id', 'LEFT');

            $this->datatables->Join('sma_brands ', 'sma_brands.id = sma_products.brand', 'LEFT');

            if ($product) {
                $this->datatables->where('sma_products.id', $product);
            }

            if ($category) {
                $this->datatables->where('sma_categories.id', $category);
            }

            if ($subcategory) {
                $this->datatables->where('scategory.id', $subcategory);
            }

            if ($brand) {
                $this->datatables->where('sma_brands.id', $brand);
            }

            if ($user) {
                $this->datatables->where('sma_transfer_request.created_by', $user);
            }


            if ($fwarehouse) {
                $this->datatables->where('sma_transfer_request_items.warehouse_id', $fwarehouse);
            }

            $this->datatables->where_in('sma_transfer_request.status', 'pending');
            $this->datatables->group_by('sma_transfer_request_items.product_id');

            foreach ($warehouses as $warehousesitem) {
                $this->datatables->add_column($warehousesitem->id, '$1', 'warehouseqty("sma_transfer_request_items.product_id",' . $warehousesitem->id . ')');
            }


            $this->datatables->unset_column('sma_transfer_request_items.product_id');
            $this->db->order_by('sma_products.name', 'ASC');
            echo $this->datatables->generate();
        }
    }

    public function deposit() {
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Deposit_Recharge_Report')));
        $meta = array('page_title' => lang('Deposit_Recharge_Report'), 'bc' => $bc);
        $this->page_construct('reports/deposit', $meta, $this->data);
    }

    public function get_deposit_report($pdf = NULL, $xls = NULL, $img = NULL) {
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;


        if ($pdf || $xls || $img) {


            $this->db->select('sma_deposits.date,sma_companies.name,sma_companies.phone,sma_companies.cf1,sma_companies.cf2, sma_deposits.amount,sma_deposits.super_cash,sma_deposits.paid_by, sma_deposits.note, CONCAT(sma_users.first_name, sma_users.last_name) as created_by,sma_deposits.id');
//            $this->db->from('sma_deposits');
            $this->db->join('sma_companies', 'sma_companies.id = sma_deposits.company_id', 'left');
            $this->db->join('sma_users', 'sma_users.id = sma_deposits.created_by', 'left');
            if ($start_date && $end_date) {
                $this->db->where('sma_deposits.date ' . ' BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            $this->db->order_by('sma_deposits.id', 'ASC');
            $data = $this->db->get('sma_deposits')->result();

            if (!empty($data)) {
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:I1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Deposit Report');
                $this->excel->getActiveSheet()->setTitle(lang('Deposit Report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Customer Name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Phone No'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Card No'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Room No'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Amount'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Super Cash'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('Total Amount'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Paid By'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Note'));
                $this->excel->getActiveSheet()->SetCellValue('K2', lang('Created By'));




                $row = 3;

                foreach ($data as $data_row) {

                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->date);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->phone);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->cf1);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->cf2);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, ($data_row->amount - $data_row->super_cash));
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->super_cash);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->amount);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->paid_by);
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, strip_tags($data_row->note));
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, $data_row->created_by);




                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(30);
            }

            $filename = 'Deposit Reports';
            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            /* echo $filename;
              exit; */
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



            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            $this->load->helper('reports');
            $this->datatables->select('sma_deposits.date,sma_companies.name,sma_companies.phone,sma_companies.cf1,sma_companies.cf2,(sma_deposits.amount - sma_deposits.super_cash ) as deposit_amount , sma_deposits.super_cash ,sma_deposits.amount, sma_deposits.paid_by, sma_deposits.note, CONCAT(sma_users.first_name, sma_users.last_name),sma_deposits.id, sma_deposits.company_id');

            $this->datatables->from('sma_deposits');
            $this->datatables->join('sma_companies', 'sma_companies.id = sma_deposits.company_id', 'left');
            $this->datatables->join('sma_users', 'sma_users.id = sma_deposits.created_by', 'left');


//            if($user){
//                 $this->datatables->where('sma_transfer_request.created_by',$user);
//            }

            if ($start_date && $end_date) {
                $this->datatables->where('sma_deposits.date ' . ' BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }
            $this->datatables->group_by('sma_companies.id');
            echo $this->datatables->generate();
        }
    }

    /**
     * Get Deposit
     */
    public function getdeposit() {

        $customer_id = $this->input->get('customer_id');
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        if ($start_date) {
            $start_date = $this->sma->fld($start_date);
            $end_date = trim($this->sma->fld($end_date));
        }

        $response = $this->reports_model->getDepositReEx($customer_id, $start_date, $end_date);

        echo json_encode($response);
    }

    public function customer_ledger() {
        $this->sma->checkPermissions();
        $this->data['customers'] = $this->reports_model->getCustomerCompanies();

        $this->data['biller'] = $this->site->getCompanyByID($this->pos_settings->default_biller);

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        if ($this->input->post('start_date')) {
            $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
        } else {
            $dt = "Till " . $this->input->post('end_date');
        }
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Customer_Ledgers')));
        $meta = array('page_title' => lang('Customer_Ledgers'), 'bc' => $bc);
        $this->page_construct('reports/customer_ledgers', $meta, $this->data);
    }

    /**
     * Get Customer Ledger
     */
    public function getCustomerLedger() {
        $customer_id = isset($_REQUEST['customer']) ? $_REQUEST['customer'] : NULL;
        $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : NULL;
        $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : NULL;
        if ($start_date) {
            $startDate = trim($this->sma->fld($start_date));
            $enddate = trim($end_date ? $this->sma->fld($end_date) : date('Y-m-d'));
        }

        $transactionData = $this->reports_model->getCustomerLedger($customer_id, $startDate, $enddate);


        if (isset($_GET['export'])) {

            if (!empty($transactionData)) {

                $biller = $this->site->getCompanyByID($this->pos_settings->default_biller);

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:E1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:E1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Customer Ledgers');
                $this->excel->getActiveSheet()->setTitle(lang('Customer Ledgers'));

                $this->excel->getActiveSheet()->getStyle("A2:E2")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A2:E2');
                $this->excel->getActiveSheet()->SetCellValue('A2', $biller->company);

                $this->excel->getActiveSheet()->getStyle("A3:E3")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A3:E3');
                $this->excel->getActiveSheet()->SetCellValue('A3', $biller->address);

                $this->excel->getActiveSheet()->getStyle("A4:E4")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A4:E4');
                $this->excel->getActiveSheet()->SetCellValue('A4', $biller->city . ', ' . $biller->state . '-' . $biller->postal_code);

                $this->excel->getActiveSheet()->getStyle("A5:E5")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A5:E5');
                $this->excel->getActiveSheet()->SetCellValue('A5', 'Phone : ' . $biller->phone . ' Email : ' . $biller->email);

                $this->excel->getActiveSheet()->getStyle("A6:E6")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A6:E6');
                $this->excel->getActiveSheet()->SetCellValue('A6', 'GSTIN : ' . $biller->gstn_no);

                $customer_details = $this->reports_model->getCustomerName($customer_id);
                $this->excel->getActiveSheet()->getStyle("A7:E7")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A7:E7');
                $this->excel->getActiveSheet()->SetCellValue('A7', 'Ledger Account : ' . $customer_details->name);
                if ($_REQUEST['start_date']) {
                    $this->excel->getActiveSheet()->getStyle("A8:E8")->applyFromArray($style);
                    $this->excel->getActiveSheet()->mergeCells('A8:E8');
                    $this->excel->getActiveSheet()->SetCellValue('A8', 'Date : ' . $_REQUEST['start_date'] . ' - ' . $_REQUEST['end_date']);
                }


                $this->excel->getActiveSheet()->SetCellValue('A9', lang('Date'));
                $this->excel->getActiveSheet()->SetCellValue('B9', lang('Particulars'));
                $this->excel->getActiveSheet()->SetCellValue('C9', lang('Debit'));
                $this->excel->getActiveSheet()->SetCellValue('D9', lang('Credit'));
                $this->excel->getActiveSheet()->SetCellValue('E9', lang('Balance'));

                $row = 10;
                $TotalDabit = $TotalCredit = $Totalbalance = 0;

                foreach ($transactionData as $rowdata) {
//                        $this->excel->getActiveSheet()->getStyle("A" . $row . ":E" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                    if ($rowdata['invoice_no']) {
                        $dabit = $rowdata['grand_total'];
                        $TotalDabit += $dabit;
                        $Totalbalance += $dabit;
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, date('M d', strtotime($rowdata['date'])));
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, ' Bill No. ' . $rowdata['invoice_no'] . ($rowdata['sale_status'] == 'returned' ? '(Returned)' : ''));
                        $this->excel->getActiveSheet()->SetCellValue('C' . $row, number_format($dabit, 2));
                        $this->excel->getActiveSheet()->SetCellValue('D' . $row, '');
                        $this->excel->getActiveSheet()->SetCellValue('E' . $row, number_format($Totalbalance, 2));
                        $row ++;
                    }

//                          $this->excel->getActiveSheet()->getStyle("A" . $row . ":E" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

                    if ($rowdata['paid_by']) {
                        $credit = $rowdata['paid_amount'];
                        $TotalCredit += $credit;
                        $Totalbalance -= $credit;
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, date('M d', strtotime($rowdata['date'])));
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, strtoupper($rowdata['paid_by']) . ' RECIVED BY ' . strtoupper($rowdata['customer']));
                        $this->excel->getActiveSheet()->SetCellValue('C' . $row, '');
                        $this->excel->getActiveSheet()->SetCellValue('D' . $row, number_format($credit, 2));
                        $this->excel->getActiveSheet()->SetCellValue('E' . $row, number_format($Totalbalance, 2));
                        $row ++;
                    }
                }//end foreach
//                $this->excel->getActiveSheet()->getStyle("C" . $row . ":E" . $row)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                $this->excel->getActiveSheet()->SetCellValue('A' . $row, 'Total');

                $this->excel->getActiveSheet()->SetCellValue('C' . $row, $TotalDabit);
                $this->excel->getActiveSheet()->SetCellValue('D' . $row, $TotalCredit);
                $this->excel->getActiveSheet()->SetCellValue('E' . $row, $Totalbalance);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);

                $filename = 'products_ledgers';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if ($_GET['export'] == 'pdf') {
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
                if ($_GET['export'] == 'xls') {
                    $this->excel->getActiveSheet()->getStyle('C2:O' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                if ($_GET['export'] == 'img') {
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_ledgers.pdf', __FILE__));
                    redirect("reports/create_image/products_ledgers.pdf");
                    exit();
                }
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
            $countData = count($getData);

            $totalRows = $countData;
            $per_page_rows = 10;
            $totalPages = ceil($totalRows / $per_page_rows);

            $pagignation = $this->sma->ajax_pagignations($totalPages, $page);

            $tableHeader = '<table class="table table-striped table-bordered table-condensed table-hover dfTable reports-table" style="margin-bottom:5px;">
                            <thead>                               
                            <tr> 
                                <th>Date</th>
                                <th>Particulars</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Balance</th>
                                                             
                            </tr>
                            </thead>
                            <tbody>';

            $tableRow = '';
//                echo '<pre>';
//                print_r($transactionData);
//                echo '</pre>';
            $TotalDabit = $TotalCredit = $Totalbalance = 0;
            foreach ($transactionData as $rowdata) {

                if ($rowdata['invoice_no']) {
                    $dabit = $rowdata['grand_total'];
                    $TotalDabit += $dabit;
                    $Totalbalance += $dabit;
                    $tableRow .= '<tr>';
                    $tableRow .= '<td class="text-center"> ' . date('M d', strtotime($rowdata['date'])) . '</td>';
                    $tableRow .= '<td> Bill No. ' . $rowdata['invoice_no'] . ($rowdata['sale_status'] == 'returned' ? '(Returned)' : '') . ' </td>';
                    $tableRow .= '<td class="text-right"> ' . number_format($dabit, 2) . '</td>';
                    $tableRow .= '<td> </td>';
                    $tableRow .= '<td class="text-right"> ' . number_format($Totalbalance, 2) . '</td>';
                    $tableRow .= '</tr>';
                }

                if ($rowdata['paid_by']) {
                    $credit = $rowdata['paid_amount'];
                    $TotalCredit += $credit;
                    $Totalbalance -= $credit;
                    $tableRow .= '<tr>';
                    $tableRow .= '<td class="text-center"> ' . date('M d', strtotime($rowdata['date'])) . '</td>';
                    $tableRow .= '<td style="padding-left: 30px;"> ' . strtoupper($rowdata['paid_by']) . ' RECIVED BY ' . strtoupper($rowdata['customer']) . '</td>';
                    $tableRow .= '<td> </td>';
                    $tableRow .= '<td class="text-right"> ' . number_format($credit, 2) . '</td>';
                    $tableRow .= '<td class="text-right"> ' . number_format($Totalbalance, 2) . '</td>';
                    $tableRow .= '</tr>';
                }
            }//end foreach.

            $tableFooter = '</tbody>
                                <tfoot class="dtFilter">
                                <tr>                                 
                                    <th colspan="2"> &nbsp; Total</th>
                                    
                                    <th class="text-right">' . $TotalDabit . ' </th>
                                    <th class="text-right">' . $TotalCredit . '</th>
                                    <th class="text-right">' . $Totalbalance . '</th>
                                </tr>
                                </tfoot>
                            </table>';


            echo $pagignation . $tableHeader . $tableRow . $tableFooter . $pagignation;
        }
    }

    /**
     * Deposit History ladger
     */
    public function depositHistory() {

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Deposit_History')));
        $meta = array('page_title' => lang('Deposit_History'), 'bc' => $bc);
        $this->page_construct('reports/deposit_history', $meta, $this->data);
    }

    /**
     * Get Deposti History
     */
    public function getDepositHistory($pdf = NULL, $xls = NULL, $img = NULL) {
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;


        if ($pdf || $xls || $img) {


            $this->db->select("DATE_FORMAT(sma_customer_deposit_opening_balance.date, '%d/%m/%Y') as date ,sma_companies.name,sma_companies.phone,sma_companies.cf1,sma_companies.cf2, sma_customer_deposit_opening_balance.opening_balance,( sma_customer_deposit_opening_balance.opening_balance -  sma_customer_deposit_opening_balance.closing_balance ) as use_deposit , sma_customer_deposit_opening_balance.closing_balance, sma_customer_deposit_opening_balance.customer_id");
            $this->db->join('sma_companies', 'sma_companies.id =  sma_customer_deposit_opening_balance.customer_id', 'left');

            if ($start_date && $end_date) {
                $this->db->where('sma_customer_deposit_opening_balance.date ' . ' BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }


            $this->db->order_by('sma_customer_deposit_opening_balance.id', 'ASC');
            $data = $this->db->get('sma_customer_deposit_opening_balance')->result();

            if (!empty($data)) {
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:I1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:I1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Deposit History Report');
                $this->excel->getActiveSheet()->setTitle(lang('Deposit History  Report'));
                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Customer Name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Phone No'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Card No'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Room No'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Opening Balance'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Recharge '));


                $this->excel->getActiveSheet()->SetCellValue('H2', lang('Use Amount'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Closing Balance'));

                $row = 3;

                foreach ($data as $data_row) {
                    $this->load->model('companies_model');
                    $date = $this->sma->fld(trim($data_row->date));

                    $customerId = $data_row->customer_id;

                    $result = $this->companies_model->getTotalReacharge($date, $customerId);
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->date);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->phone);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->cf1);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->cf2);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->opening_balance);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $result->totalAmt);

                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->use_deposit);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->closing_balance);


                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(30);
            }

            $filename = 'Deposit Reports';
            $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            /* echo $filename;
              exit; */
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



            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->load->library('datatables');
            $this->load->helper('reports');
            $this->datatables->select("DATE_FORMAT(sma_customer_deposit_opening_balance.date, '%d/%m/%Y') as date ,sma_companies.name,sma_companies.phone,sma_companies.cf1,sma_companies.cf2, sma_customer_deposit_opening_balance.opening_balance, 0 as recharge ,( sma_customer_deposit_opening_balance.opening_balance -  sma_customer_deposit_opening_balance.closing_balance ) as use_deposit , sma_customer_deposit_opening_balance.closing_balance, sma_customer_deposit_opening_balance.customer_id");
            $this->datatables->from('sma_customer_deposit_opening_balance');
            $this->datatables->join('sma_companies', 'sma_companies.id =  sma_customer_deposit_opening_balance.customer_id', 'left');
            if ($start_date && $end_date) {
                $this->datatables->where('sma_customer_deposit_opening_balance.date ' . ' BETWEEN "' . $start_date . '" and "' . $end_date . '"');
            }


            // $this->datatables->where(['Date(sma_deposits.date)' => 'sma_customer_deposit_opening_balance.date']);



            echo $this->datatables->generate();
        }
    }

    /**
     * end Deposit History Ladger
     */

    /**
     * Customer Deposit Ladgers
     */
    public function customerDepositLedger() {

        $this->data['customers'] = $customersData = $this->reports_model->getCustomerWalletsList();

        $this->data['biller'] = $this->site->getCompanyByID($this->pos_settings->default_biller);
                
        if (isset($_GET['export']) && $customersData) {
            
            $this->load->library('excel');
            $this->excel->setActiveSheetIndex(0);
            $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

            $this->excel->getActiveSheet()->getStyle("A1:H1")->applyFromArray($style);
            $this->excel->getActiveSheet()->mergeCells('A1:H1');
            $this->excel->getActiveSheet()->SetCellValue('A1', 'Customer Deposit List');
            $this->excel->getActiveSheet()->setTitle(lang('Customer Deposit List'));
            
            $this->excel->getActiveSheet()->SetCellValue('A2', lang('SR'));
            $this->excel->getActiveSheet()->SetCellValue('B2', lang('Customer Name'));
            $this->excel->getActiveSheet()->SetCellValue('C2', lang('Phone'));
            $this->excel->getActiveSheet()->SetCellValue('D2', lang('Card No.'));
            $this->excel->getActiveSheet()->SetCellValue('E2', lang('Room No'));
            $this->excel->getActiveSheet()->SetCellValue('F2', lang('Total Deposit Amount'));
            $this->excel->getActiveSheet()->SetCellValue('G2', lang('Balance Amount'));
            $this->excel->getActiveSheet()->SetCellValue('H2', lang('Spent Amount (In Record)'));

            $row = 3;
            $TotalDeposit = $TotalSpent = $Totalbalance = 0;
            $sr = 0;
            foreach ($customersData as $customer) {
            
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, ++$sr);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, ucwords($customer->name));
                        $this->excel->getActiveSheet()->SetCellValue('C' . $row, $customer->phone);
                        $this->excel->getActiveSheet()->SetCellValue('D' . $row, $customer->card_no);
                        $this->excel->getActiveSheet()->SetCellValue('E' . $row, $customer->room_no);
                        $this->excel->getActiveSheet()->SetCellValue('F' . $row, $customer->total_deposit); 
                        $this->excel->getActiveSheet()->SetCellValue('G' . $row, $customer->balance_amount); 
                        $this->excel->getActiveSheet()->SetCellValue('H' . $row, $customer->spent_amount); 
                        
//                        $TotalDeposit   += $customer->total_deposit;
//                        $TotalSpent     += $customer->spent_amount;
//                        $Totalbalance   += $customer->balance_amount;
                        
                        $row++;
                        
                }//end foreach

//                $this->excel->getActiveSheet()->SetCellValue('A' . $row, 'Total');
//            
//                $this->excel->getActiveSheet()->SetCellValue('F' . $row, $TotalDeposit);
//                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $TotalSpent);            
//                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $Totalbalance);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);

                $filename = 'customer_deposit_list';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                
                if ($_GET['export'] == 'xls') {
                    $this->excel->getActiveSheet()->getStyle('C2:H' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
                
                $this->session->set_flashdata('error', lang('nothing_found'));
                redirect($_SERVER["HTTP_REFERER"]);
                
        } else {
        
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            if ($this->input->post('start_date')) {
                $dt = "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
            } else {
                $dt = "Till " . $this->input->post('end_date');
            }
            $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('Customer Deposit Ledgers')));
            $meta = array('page_title' => lang('Customer Deposit Ledgers'), 'bc' => $bc);
            $this->page_construct('reports/deposit_customer_ledger', $meta, $this->data);
        
        }
    }

    /**
     * Get Customer Deposit ladgers
     *  */
    public function getCustomerDepositLedger() {

        $customer_id = isset($_REQUEST['customer']) ? $_REQUEST['customer'] : NULL;
        $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : NULL;
        $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : NULL;

        if ($start_date) {
            $startDate = trim($this->sma->fld($start_date));
            $enddate = trim($end_date ? $this->sma->fld($end_date) : date('Y-m-d'));
        }

        $transactionData = $this->reports_model->getCustomerDepositLedger($customer_id, $startDate, $enddate);


        if (isset($_GET['export'])) {

            if (!empty($transactionData)) {

                $biller = $this->site->getCompanyByID($this->pos_settings->default_biller);

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $style = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,), 'font' => array('name' => 'Arial', 'color' => array('rgb' => 'FF0000')), 'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_NONE, 'color' => array('rgb' => 'FF0000'))));

                $this->excel->getActiveSheet()->getStyle("A1:J1")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A1:J1');
                $this->excel->getActiveSheet()->SetCellValue('A1', 'Customer Deposit Ledgers');
                $this->excel->getActiveSheet()->setTitle(lang('Customer Deposit Ledgers'));

              /*  $this->excel->getActiveSheet()->getStyle("A2:E2")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A2:E2');
                $this->excel->getActiveSheet()->SetCellValue('A2', $biller->company);

                $this->excel->getActiveSheet()->getStyle("A3:E3")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A3:E3');
                $this->excel->getActiveSheet()->SetCellValue('A3', $biller->address);

                $this->excel->getActiveSheet()->getStyle("A4:E4")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A4:E4');
                $this->excel->getActiveSheet()->SetCellValue('A4', $biller->city . ', ' . $biller->state . '-' . $biller->postal_code);

                $this->excel->getActiveSheet()->getStyle("A5:E5")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A5:E5');
                $this->excel->getActiveSheet()->SetCellValue('A5', 'Phone : ' . $biller->phone . ' Email : ' . $biller->email);

                $this->excel->getActiveSheet()->getStyle("A6:E6")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A6:E6');
                $this->excel->getActiveSheet()->SetCellValue('A6', 'GSTIN : ' . $biller->gstn_no);

                $customer_details = $this->reports_model->getCustomerName($customer_id);
                $this->excel->getActiveSheet()->getStyle("A7:E7")->applyFromArray($style);
                $this->excel->getActiveSheet()->mergeCells('A7:E7');
                $this->excel->getActiveSheet()->SetCellValue('A7', 'Customer Name : ' . $customer_details->name);
                if ($_REQUEST['start_date']) {
                    $this->excel->getActiveSheet()->getStyle("A8:E8")->applyFromArray($style);
                    $this->excel->getActiveSheet()->mergeCells('A8:E8');
                    $this->excel->getActiveSheet()->SetCellValue('A8', 'Date : ' . $_REQUEST['start_date'] . ' - ' . $_REQUEST['end_date']);
                }*/


                $this->excel->getActiveSheet()->SetCellValue('A2', lang('Date'));
                $this->excel->getActiveSheet()->SetCellValue('B2', lang('Customer Name'));
                $this->excel->getActiveSheet()->SetCellValue('C2', lang('Phone'));
                $this->excel->getActiveSheet()->SetCellValue('D2', lang('Card No.'));
                $this->excel->getActiveSheet()->SetCellValue('E2', lang('Room No'));
                $this->excel->getActiveSheet()->SetCellValue('F2', lang('Opening Amount'));
                $this->excel->getActiveSheet()->SetCellValue('G2', lang('Recharge'));
                $this->excel->getActiveSheet()->SetCellValue('H2', lang('Spent'));
                $this->excel->getActiveSheet()->SetCellValue('I2', lang('Clossing Amount'));
                $this->excel->getActiveSheet()->SetCellValue('J2', lang('Balance Amount'));

                $row = 3;
                $TotalReachange = $TotalSpent = $Totalbalance = 0;

                foreach ($transactionData as $rowdata) {
            
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, $rowdata['date']);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, $rowdata['name']);
                        $this->excel->getActiveSheet()->SetCellValue('C' . $row, $rowdata['phone']);
                        $this->excel->getActiveSheet()->SetCellValue('D' . $row, $rowdata['card_no']);
                        $this->excel->getActiveSheet()->SetCellValue('E' . $row, $rowdata['room_no']);
                        $this->excel->getActiveSheet()->SetCellValue('F' . $row, $rowdata['opening_balance']); 
                        $this->excel->getActiveSheet()->SetCellValue('G' . $row, $rowdata['recharge_amount']); 
                        $this->excel->getActiveSheet()->SetCellValue('H' . $row, $rowdata['spent_amount']); 
                        $this->excel->getActiveSheet()->SetCellValue('I' . $row, $rowdata['closing_balance']); 
                        $this->excel->getActiveSheet()->SetCellValue('J' . $row, $rowdata['balance_amount']); 
                        
                        $TotalReachange += $rowdata['recharge_amount'];
                        $TotalSpent     += $rowdata['spent_amount'];
                        $Totalbalance   += $rowdata['balance_amount'];
                        
                        $row++;
                        
                }//end foreach

                $this->excel->getActiveSheet()->SetCellValue('A' . $row, 'Total');

            
                $this->excel->getActiveSheet()->SetCellValue('G' . $row, $TotalReachange);
                $this->excel->getActiveSheet()->SetCellValue('H' . $row, $TotalSpent);            
                $this->excel->getActiveSheet()->SetCellValue('J' . $row, $Totalbalance);

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(25);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(25);

                $filename = 'customer_deposit_ledgers';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
               /* if ($_GET['export'] == 'pdf') {
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
                }*/
                if ($_GET['export'] == 'xls') {
                    $this->excel->getActiveSheet()->getStyle('C2:O' . $row)->getAlignment()->setWrapText(TRUE);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }
               /* if ($_GET['export'] == 'img') {
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
                    $objWriter->save(str_replace(__FILE__, 'assets/uploads/pdf/products_ledgers.pdf', __FILE__));
                    redirect("reports/create_image/products_ledgers.pdf");
                    exit();
                }*/
            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $tableHeader = '<table class="table table-striped table-bordered table-condensed table-hover dfTable reports-table" style="margin-bottom:5px;">
                            <thead>                               
                            <tr> 
                                <th>Date</th>
                                <th>Customer Name</th>
                                <th>Phone</th>
                                <th>Card No.</th>
                                <th>Room No.</th>
                                <th>Opening Amount</th>
                                <th>Recharge</th>
                                <th>Spent</th>                                
                                <th>Clossing Amount</th>                                                                                           
                                <th>Balance Amount</th>                                                             
                            </tr>
                            </thead>
                            <tbody>';

            $tableRow = '';

            $TotalReachange = $TotalSpent = $Totalbalance = 0;
            if (is_array($transactionData)) {
                foreach ($transactionData as $rowdata) {

                    $tableRow .= '<tr>';
                    $tableRow .= '<td class="text-center"> ' . $rowdata['date'] . '</td>';
                    $tableRow .= '<td> ' . $rowdata['name'] . ' </td>';
                    $tableRow .= '<td> ' . $rowdata['phone'] . ' </td>';
                    $tableRow .= '<td> ' . $rowdata['card_no'] . ' </td>';
                    $tableRow .= '<td> ' . $rowdata['room_no'] . ' </td>';

                    $tableRow .= '<td class="text-right"> ' . number_format($rowdata['opening_balance'], 2) . ' </td>';
                    $tableRow .= '<td class="text-right"> ' . number_format($rowdata['recharge_amount'], 2) . ' </td>';
                    $tableRow .= '<td class="text-right"> ' . number_format($rowdata['spent_amount'], 2) . ' </td>';
                    
                    $tableRow .= '<td class="text-right"> ' . number_format($rowdata['closing_balance'], 2) . ' </td>';
                    $tableRow .= '<td class="text-right"> ' . number_format($rowdata['balance_amount'], 2) . ' </td>';

                    $tableRow .= '</tr>';

                    $TotalReachange += $rowdata['recharge_amount'];
                    $TotalSpent += $rowdata['spent_amount'];
                    $Totalbalance += $rowdata['balance_amount'];
                }//end foreach.
            }
            $tableFooter = '</tbody>
                                <tfoot class="dtFilter">
                                <tr>                                 
                                    <th colspan="5"> &nbsp; Total</th> 
                                    <th class="text-right">&nbsp;</th>
                                    <th class="text-right">' . number_format($TotalReachange, 2) . ' </th>
                                    <th class="text-right">' . number_format($TotalSpent, 2) . '</th>                                    
                                    <th class="text-right">&nbsp;</th>                                      
                                    <th class="text-right">' . number_format($Totalbalance, 2) . '</th>
                                </tr>
                                </tfoot>
                            </table>';


            echo $tableHeader . $tableRow . $tableFooter;
        }
    }

    /**
     * End
     */
}
