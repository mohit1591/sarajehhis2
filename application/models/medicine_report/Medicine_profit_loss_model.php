<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medicine_profit_loss_model extends CI_Model {

    var $table = 'hms_medicine_entry';
    var $column = array('hms_medicine_entry.id','hms_medicine_entry.medicine_code', 'hms_medicine_entry.medicine_name', 'hms_medicine_entry.unit_id','hms_medicine_entry.unit_second_id','hms_medicine_entry.conversion','hms_medicine_entry.min_alrt','hms_medicine_entry.packing','hms_medicine_entry.rack_no','hms_medicine_entry.salt','hms_medicine_entry.manuf_company','hms_medicine_entry.mrp','hms_medicine_entry.purchase_rate','hms_medicine_entry.discount','hms_medicine_entry.vat','hms_medicine_entry.status', 'hms_medicine_entry.created_date', 'hms_medicine_entry.modified_date');  
    var $order = array('hms_medicine_entry.id' => 'desc'); 

    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function _get_datatables_query()
    {
        $users_data = $this->session->userdata('auth_users');
        $search_data = $this->session->userdata('search_data');
        $this->db->select("hms_opd_booking.*, hms_doctors.doctor_name, hms_patient.patient_name"); 
        $this->db->join('hms_patient','hms_patient.id = hms_opd_booking.patient_id');
        //$this->db->join('hms_department','hms_department.id = hms_opd_booking.dept_id');
        $this->db->join('hms_doctors','hms_doctors.id = hms_opd_booking.attended_doctor','left');

        $this->db->from($this->table);  
        $this->db->where('hms_opd_booking.branch_id',$users_data['parent_id']);
        if(isset($search_data) && !empty($search_data))
        {
            if(isset($search_data['start_date']) && !empty($search_data['start_date'])
                )
            {
                $start_date = date('Y-m-d',strtotime($search_data['start_date']));
                $this->db->where('hms_opd_booking.booking_date >= "'.$start_date.'"');
            }

            if(isset($search_data['end_date']) && !empty($search_data['end_date'])
                )
            {
                $end_date = date('Y-m-d',strtotime($search_data['end_date']));
                $this->db->where('hms_opd_booking.booking_date <= "'.$end_date.'"');
            }

            /*if(isset($search_data['dept_id']) && !empty($search_data['dept_id'])
                )
            { 
                $this->db->where('hms_opd_booking.booking_date = "'.$search_data["dept_id"].'"');
            }
*/
            if(isset($search_data['referral_doctor']) && !empty($search_data['referral_doctor'])
                )
            { 
                $this->db->where('hms_doctors.id = "'.$search_data["referral_doctor"].'"');
                $this->db->where('hms_doctors.doctor_type IN (0,2)');
            }
            
            if(isset($search_data['attended_doctor']) && !empty($search_data['attended_doctor'])
                )
            { 
                $this->db->where('hms_doctors.id = "'.$search_data["attended_doctor"].'"');
                $this->db->where('hms_doctors.doctor_type IN (1,2)');
            }
            
            if(isset($search_data['patient_name']) && !empty($search_data['patient_name'])
                )
            { 
                $this->db->where('hms_patient.patient_name LIKE "'.$search_data["patient_name"].'%"');
            }
            
            if(isset($search_data['patient_code']) && !empty($search_data['patient_code'])
                )
            { 
                $this->db->where('hms_patient.patient_code LIKE "'.$search_data["patient_code"].'%"');
            }
            
            if(isset($search_data['mobile_no']) && !empty($search_data['mobile_no'])
                )
            { 
                $this->db->where('hms_patient.mobile_no LIKE "'.$search_data["mobile_no"].'%"');
            }
            
            if(isset($search_data['profile_id']) && !empty($search_data['profile_id'])
                )
            { 
                $this->db->where('hms_opd_booking.profile_id = "'.$search_data["profile_id"].'"');
            }
            
            if(isset($search_data['sample_collected_by']) && !empty($search_data['sample_collected_by'])
                )
            { 
                $this->db->where('hms_opd_booking.sample_collected_by = "'.$search_data["sample_collected_by"].'"');
            }
            
            if(isset($search_data['staff_refrenace_id']) && !empty($search_data['staff_refrenace_id'])
                )
            { 
                $this->db->where('hms_opd_booking.staff_refrenace_id = "'.$search_data["staff_refrenace_id"].'"');
            }
            
        }
        $i = 0;
    
        foreach ($this->column as $item) // loop column 
        {
            if($_POST['search']['value']) // if datatable send POST for search
            {
                
                if($i===0) // first loop
                {
                    $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND. 
                    $this->db->like($item, $_POST['search']['value']);
                }
                else
                {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if(count($this->column) - 1 == $i) //last loop+
                    $this->db->group_end(); //close bracket
            }
            $column[$i] = $item; // set column array variable to order processing
            $i++;
        }
        
        if(isset($_POST['order'])) // here order processing
        {
            $this->db->order_by($column[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        }
        else if(isset($this->order))
        {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_datatables($branch_id='')
    {
        $this->_get_datatables_query($branch_id);
        if($_POST['length'] != -1)
        $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get(); 
        //echo $this->db->last_query();die;
        return $query->result();
    }
    function medicinequantityreport_list($get="")
    {
        $user_data = $this->session->userdata('auth_users');
        $p_ids=array();
        $p_r_ids=array();
        $s_ids=array();
        $s_r_ids=array();
        $new_s_r_ids='';
        $new_s_ids='';
        $new_p_r_ids='';
        $new_p_ids='';
        $where_1='';
        $where_2='';
        $where_3='';
        $where_4='';
        $type=1;
        $type1=2;
        $type2=3;
        $type3=4;
        $deleted_purchase_medicine= $this->is_deleted_purchase_medicine();
        $deleted_purchase_return_medicine= $this->is_deleted_purchase_return_medicine();
        $deleted_sale_medicine= $this->is_deleted_sale_medicine();
        $deleted_sale_return_medicine= $this->is_deleted_sale_return_medicine();
      
        $this->db->select("hms_medicine_entry.medicine_code,hms_medicine_entry.medicine_name as med_name,hms_medicine_entry.id,hms_medicine_entry.medicine_name as name");
        $this->db->where('hms_medicine_entry.is_deleted','0'); 
       
        $this->db->join('hms_medicine_company','hms_medicine_company.id = hms_medicine_entry.manuf_company','left');
        $this->db->group_by('hms_medicine_entry.id');
        
        $this->db->from('hms_medicine_entry'); 
        $this->db->where('hms_medicine_entry.branch_id',$user_data['parent_id']); 
        //$this->db->limit(10); 
        $data['medicine_list']= $this->db->get()->result_array();
        //echo $this->db->last_query();die;

        $data_array=[];
        $i=0;


        foreach($data['medicine_list'] as $medicine_list)
        {
            $data_array[$i]['id']=$medicine_list['id'];
            $data_array[$i]['medicine_name']=$medicine_list['name'];
            $data_array[$i]['med_name']=$medicine_list['med_name'];
            $data_array[$i]['medicine_code']=$medicine_list['medicine_code'];
            
            /*  code for deleted record */
            
            /* for puchase code here */
            if($type=1)
            {

                $this->db->select('sum(hms_medicine_purchase_to_purchase.total_amount) as total_amt, hms_medicine_purchase_to_purchase.qty as total_qty,hms_medicine_purchase_to_purchase.batch_no');
                //$this->db->join('hms_medicine_entry','hms_medicine_entry.id = hms_medicine_purchase_to_purchase.medicine_id'); 
                //$this->db->where('hms_medicine_purchase_to_purchase.purchase_id = "'.$ids.'"');
                //$this->db->where('hms_medicine_purchase_to_purchase.branch_id = "'.$branch_id.'"'); 
                $this->db->from('hms_medicine_purchase_to_purchase');

                if(!empty($get['start_date']))
                {
                    $from_c_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
                    $this->db->where('hms_medicine_purchase_to_purchase.created_date >= "'.$from_c_date.'"');
                }

                if(!empty($get['end_date']))
                {
                    $to_c_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
                    $this->db->where('hms_medicine_purchase_to_purchase.created_date <= "'.$to_c_date.'"');
                }
                $this->db->where('hms_medicine_purchase_to_purchase.medicine_id',$medicine_list['id']);
                
                $data_array[$i]['purchsase_quantity'] = $this->db->get()->row_array();
                //echo $this->db->last_query(); exit;


                /*$this->db->select("(sum(debit)-sum(credit)) as total_qty,sum(total_amount) as total_amt,hms_medicine_purchase.paid_amount");
                if(isset($search['branch_id']) && $search['branch_id']!='')
                {
                    $this->db->where('branch_id IN ('.$search['branch_id'].')');
                }
                else
                {
                    $this->db->where('branch_id',$user_data['parent_id']);
                }
                if(!empty($deleted_purchase_medicine))
                {
                    foreach($deleted_purchase_medicine as $purchase_ids)
                    {
                        $p_ids[]=$purchase_ids['id'];
                    }
                    $new_p_ids=implode(',',$p_ids);
                    $this->db->where("hms_medicine_stock.parent_id NOT IN(".$new_p_ids.")");   
                }

                    $this->db->join('hms_medicine_purchase','hms_medicine_purchase.id = hms_medicine_stock.parent_id');

                    $this->db->where('hms_medicine_stock.m_id',$medicine_list['id']);
                    $this->db->where('hms_medicine_stock.type',1);
                    $this->db->where('hms_medicine_stock.m_id',$medicine_list['id']);
                    $this->db->where('hms_medicine_stock.branch_id',$user_data['parent_id']);
                    if(!empty($get['start_date']))
                    {
                        $from_c_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
                        $this->db->where('hms_medicine_stock.created_date >= "'.$from_c_date.'"');
                    }

                    if(!empty($get['end_date']))
                    {
                        $to_c_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
                        $this->db->where('hms_medicine_stock.created_date <= "'.$to_c_date.'"');
                    }
                    $this->db->get('hms_medicine_stock');
                 /* echo $this->db->last_query(); exit;
                    $data_array[$i]['purchsase_quantity'] = $this->db->get('hms_medicine_stock')->row_array();
                    echo $this->db->last_query(); exit;*/
            }

             /* for puchase code here */

             /* for puchase return code here */
            if($type=2)
            {


               /* $this->db->select('hms_medicine_purchase_to_purchase.*,hms_medicine_purchase_to_purchase.purchase_rate as p_r,hms_medicine_purchase_to_purchase.sgst as m_sgst,hms_medicine_purchase_to_purchase.igst as m_igst,hms_medicine_purchase_to_purchase.cgst as m_cgst,hms_medicine_purchase_to_purchase.discount as m_disc');
               $this->db->from('hms_medicine_purchase_to_purchase');

                if(!empty($get['start_date']))
                {
                    $from_c_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
                    $this->db->where('hms_medicine_purchase_to_purchase.created_date >= "'.$from_c_date.'"');
                }

                if(!empty($get['end_date']))
                {
                    $to_c_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
                    $this->db->where('hms_medicine_purchase_to_purchase.created_date <= "'.$to_c_date.'"');
                }
                $this->db->where('hms_medicine_purchase_to_purchase.medicine_id',$medicine_list['id']);
                
                $data_array[$i]['purchsase_return_quantity'] = $this->db->get()->row_array();

*/
               
            }
             /* for puchase return   code here */

            /* for sale code here */
            if($type=3) // 
            {

                $this->db->select('sum(hms_medicine_sale_to_medicine.total_amount) as total_amt,hms_medicine_sale_to_medicine.qty as total_qty,hms_medicine_sale_to_medicine.batch_no');
                
                $this->db->from('hms_medicine_sale_to_medicine');

                if(!empty($get['start_date']))
                {
                    $from_c_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
                    $this->db->where('hms_medicine_sale_to_medicine.created_date >= "'.$from_c_date.'"');
                }

                if(!empty($get['end_date']))
                {
                    $to_c_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
                    $this->db->where('hms_medicine_sale_to_medicine.created_date <= "'.$to_c_date.'"');
                }
                $this->db->where('hms_medicine_sale_to_medicine.medicine_id',$medicine_list['id']);

               $data_array[$i]['sale_quantity'] = $this->db->get()->row_array();
//echo $this->db->last_query(); exit;
                /*$this->db->select("(sum(debit)-sum(credit)) as total_qty,sum(total_amount) as total_amt,hms_medicine_sale.paid_amount");
                if(isset($search['branch_id']) && $search['branch_id']!='')
                {
                    $this->db->where('branch_id IN ('.$search['branch_id'].')');
                }
                else
                {
                    $this->db->where('branch_id',$user_data['parent_id']);
                }
                
               
                if(!empty($deleted_sale_medicine))
                {
                    foreach($deleted_sale_medicine as $sale_ids)
                    {
                        $s_ids[]=$sale_ids['id'];
                    }
                    $new_s_ids=implode(',',$s_ids);
                    $this->db->where("hms_medicine_stock.parent_id NOT IN(".$new_s_ids.")");
                }
                
            
                $this->db->join('hms_medicine_sale','hms_medicine_sale.id = hms_medicine_stock.patient_id');
                    $this->db->where('hms_medicine_stock.type',3);
                    $this->db->where('hms_medicine_stock.m_id',$medicine_list['id']);
                     $this->db->where('hms_medicine_stock.branch_id',$user_data['parent_id']);

                    if(!empty($get['start_date']))
                    {
                        $from_c_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
                        $this->db->where('hms_medicine_stock.created_date >= "'.$from_c_date.'"');
                    }

                    if(!empty($get['end_date']))
                    {
                        $to_c_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
                        $this->db->where('hms_medicine_stock.created_date <= "'.$to_c_date.'"');
                    }

                    $data_array[$i]['sale_quantity'] = $this->db->get('hms_medicine_stock')->row_array();*/
            }

             /* for sale code here */

            /* for sale return code here */
            if($type=4)
            {/*
                    $this->db->select("(sum(debit)-sum(credit)) as total_qty,sum(total_amount) as total_amt,hms_medicine_sale_return.paid_amount");
                    if(isset($search['branch_id']) && $search['branch_id']!='')
                    {
                        $this->db->where('branch_id IN ('.$search['branch_id'].')');
                    }
                    else
                    {
                         $this->db->where('branch_id',$user_data['parent_id']);
                    }
                    if(!empty($deleted_sale_return_medicine))
                    {
                        foreach($deleted_sale_return_medicine as $sale_r_ids)
                        {
                            $s_r_ids[]=$sale_r_ids['id'];
                        }
                            $new_s_r_ids=implode(',',$s_r_ids);
                            $this->db->where("hms_medicine_stock.parent_id NOT IN(".$new_s_r_ids.")");
                    }

                    $this->db->join('hms_medicine_sale_return','hms_medicine_sale_return.id = hms_medicine_stock.patient_id');
                        $this->db->where('hms_medicine_stock.type',4);
                        $this->db->where('hms_medicine_stock.m_id',$medicine_list['id']);
                        $this->db->where('hms_medicine_stock.branch_id',$user_data['parent_id']);

                        if(!empty($get['start_date']))
                        {
                            $from_c_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
                            $this->db->where('hms_medicine_stock.created_date >= "'.$from_c_date.'"');
                        }

                        if(!empty($get['end_date']))
                        {
                            $to_c_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
                            $this->db->where('hms_medicine_stock.created_date <= "'.$to_c_date.'"');
                        }

                        $data_array[$i]['sale_return_quantity'] = $this->db->get('hms_medicine_stock')->row_array();
                        //echo $this->db->last_query(); exit;
            */}
            /* for sale return code here */
            
          // print_r($data_array['quantity']);;
             $i++; 
            
          
        }    
        //print '<pre>'; print_r($data_array);;die;
        return $data_array;
     //  print '<pre>'; print_r($data_array);
    }
    function is_deleted_purchase_medicine()
    {
        $users_data = $this->session->userdata('auth_users');
        $this->db->select('hms_medicine_purchase.id');
        $this->db->where('hms_medicine_purchase.is_deleted=2');
         $this->db->where('hms_medicine_purchase.branch_id',$users_data['parent_id']);
        $query= $this->db->get('hms_medicine_purchase')->result_array();
        return $query;
    }
    function is_deleted_purchase_return_medicine()
    {
        $users_data = $this->session->userdata('auth_users');
        $this->db->select('hms_medicine_return.id');
        $this->db->where('hms_medicine_return.branch_id',$users_data['parent_id']);
        $this->db->where('hms_medicine_return.is_deleted=2');
        $query=$this->db->get('hms_medicine_return')->result_array();
        //print_r($query);die;
        return $query;
    }
    function is_deleted_sale_medicine()
    {
        $users_data = $this->session->userdata('auth_users');
        $this->db->select('hms_medicine_sale.id');
        $this->db->where('hms_medicine_sale.is_deleted=2');
        $this->db->where('hms_medicine_sale.branch_id',$users_data['parent_id']);
        $query=$this->db->get('hms_medicine_sale')->result_array();
        return $query;
    }
    function is_deleted_sale_return_medicine()
    {
        $users_data = $this->session->userdata('auth_users');
        $this->db->select('hms_medicine_sale_return.id');
        $this->db->where('hms_medicine_sale_return.branch_id',$users_data['parent_id']);
        $this->db->where('hms_medicine_sale_return.is_deleted=2');
        $query=$this->db->get('hms_medicine_sale_return')->result_array();
        return $query;
    }
    function search_report_data()
    {
        $users_data = $this->session->userdata('auth_users');
        $search_data = $this->session->userdata('search_data'); 
        $this->db->select("hms_opd_booking.*, hms_doctors.doctor_name, hms_patient.patient_name"); 
        $this->db->join('hms_patient','hms_patient.id = hms_opd_booking.patient_id');
        //$this->db->join('hms_department','hms_department.id = hms_opd_booking.dept_id');
        $this->db->join('hms_doctors','hms_doctors.id = hms_opd_booking.attended_doctor','left');

        $this->db->from($this->table);  
        $this->db->where('hms_opd_booking.branch_id',$users_data['parent_id']);
        if(isset($search_data) && !empty($search_data))
        {
            if(isset($search_data['start_date']) && !empty($search_data['start_date'])
                )
            {
                $start_date = date('Y-m-d',strtotime($search_data['start_date']));
                $this->db->where('hms_opd_booking.booking_date >= "'.$start_date.'"');
            }

            if(isset($search_data['end_date']) && !empty($search_data['end_date'])
                )
            {
                $end_date = date('Y-m-d',strtotime($search_data['end_date']));
                $this->db->where('hms_opd_booking.booking_date <= "'.$end_date.'"');
            }

            /*if(isset($search_data['dept_id']) && !empty($search_data['dept_id'])
                )
            { 
                $this->db->where('hms_opd_booking.booking_date = "'.$search_data["dept_id"].'"');
            }*/

            if(isset($search_data['referral_doctor']) && !empty($search_data['referral_doctor'])
                )
            { 
                $this->db->where('hms_doctors.id = "'.$search_data["referral_doctor"].'"');
                $this->db->where('hms_doctors.doctor_type IN (0,2)');
            }
            
            if(isset($search_data['attended_doctor']) && !empty($search_data['attended_doctor'])
                )
            { 
                $this->db->where('hms_doctors.id = "'.$search_data["attended_doctor"].'"');
                $this->db->where('hms_doctors.doctor_type IN (1,2)');
            }
            
            if(isset($search_data['patient_name']) && !empty($search_data['patient_name'])
                )
            { 
                $this->db->where('hms_patient.patient_name LIKE "'.$search_data["patient_name"].'%"');
            }
            
            if(isset($search_data['patient_code']) && !empty($search_data['patient_code'])
                )
            { 
                $this->db->where('hms_patient.patient_code LIKE "'.$search_data["patient_code"].'%"');
            }
            
            if(isset($search_data['mobile_no']) && !empty($search_data['mobile_no'])
                )
            { 
                $this->db->where('hms_patient.mobile_no LIKE "'.$search_data["mobile_no"].'%"');
            }
            
                    
            
        }
        $query = $this->db->get(); 
        //echo $this->db->last_query();die;
        return $query->result();
    }

    function count_filtered()
    {
        $this->_get_datatables_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }
    
    public function get_expenses_details($get=array())
    { 
        if(!empty($get))
        {
            $users_data = $this->session->userdata('auth_users'); 
            $sub_branch_details = $this->session->userdata('sub_branches_data');
            if(!empty($sub_branch_details))
            {
                $child_ids_arr = array_column($sub_branch_details,'id');
                $child_ids = implode(',',$child_ids_arr);
            } 
           // $this->db->select("(CASE WHEN hms_expenses.type=0 THEN hms_expenses.vouchar_no WHEN hms_expenses.type=2 THEN (select purchase_id from  hms_medicine_purchase where id=hms_expenses.parent_id) ELSE '' END) as vouchar_no, (CASE WHEN hms_expenses.type=0 THEN 'expenses' WHEN hms_expenses.type=3 THEN 'Sale Return' WHEN hms_expenses.type=1 THEN 'Emp. Salary' WHEN hms_expenses.type=2 THEN 'Purchase Medicine' ELSE '' END) as type, hms_expenses.paid_amount,hms_expenses_category.exp_category, hms_expenses.created_date,hms_expenses.expenses_date"); 
            $this->db->select("(CASE WHEN hms_expenses.type=0 THEN hms_expenses.vouchar_no WHEN hms_expenses.type=2 THEN (select purchase_id from  hms_medicine_purchase where id=hms_expenses.parent_id) ELSE 'N/A' END) as vouchar_no, (CASE WHEN hms_expenses.type=0 THEN 'expenses' WHEN hms_expenses.type=3 THEN 'Sale Return' WHEN hms_expenses.type=1 THEN 'Emp. Salary' WHEN hms_expenses.type=2 THEN 'Purchase Medicine' ELSE 'N/A' END) as type, hms_expenses.paid_amount, (CASE WHEN hms_expenses.type=0 THEN hms_expenses_category.exp_category ELSE 'N/A' END) as exp_category, hms_expenses.created_date,hms_expenses.expenses_date,hms_payment_mode.payment_mode");
            $this->db->from('hms_expenses'); 
            $this->db->join("hms_expenses_category","hms_expenses.paid_to_id=hms_expenses_category.id",'left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_expenses.payment_mode','left');
            $this->db->where('hms_expenses.paid_amount>0');
            $this->db->where('hms_expenses.is_deleted!=2');
            if(!empty($get['start_date']))
            {
              $this->db->where('hms_expenses.expenses_date >= "'.date('Y-m-d',strtotime($get['start_date'])).'"');
            }

            if(!empty($get['end_date']))
            {
              $this->db->where('hms_expenses.expenses_date<= "'.date('Y-m-d',strtotime($get['end_date'])).'"');   
            }

            if(!empty($get['branch_id']))
            {
              if(is_numeric($get['branch_id']) && $get['branch_id']>0)
              {
                 $this->db->where('hms_expenses.branch_id',$get['branch_id']);  
              } 
              else if($get['branch_id']=='all') 
              {
                 $this->db->where('hms_expenses.branch_id IN ('.$child_ids.')');  
              }  
            }

            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]) && is_numeric($get['employee']))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_expenses.created_by IN ('.$emp_ids.')');
            }

            $query = $this->db->get();
            //echo $this->db->last_query(); exit;
            //$result = $query->result();
            $result_expense['expense_list'] = $query->result(); 

            $this->db->select("(CASE WHEN hms_expenses.type=0 THEN hms_expenses.vouchar_no WHEN hms_expenses.type=2 THEN (select purchase_id from  hms_medicine_purchase where id=hms_expenses.parent_id) ELSE 'N/A' END) as vouchar_no, (CASE WHEN hms_expenses.type=0 THEN 'expenses' WHEN hms_expenses.type=3 THEN 'Sale Return' WHEN hms_expenses.type=1 THEN 'Emp. Salary' WHEN hms_expenses.type=2 THEN 'Purchase Medicine' ELSE 'N/A' END) as type, hms_expenses.paid_amount, (CASE WHEN hms_expenses.type=0 THEN hms_expenses_category.exp_category ELSE 'N/A' END) as exp_category, hms_expenses.created_date,hms_expenses.expenses_date,hms_payment_mode.payment_mode,sum(hms_expenses.paid_amount) as total_amount"); 
            $this->db->from('hms_expenses'); 
            $this->db->join("hms_expenses_category","hms_expenses.paid_to_id=hms_expenses_category.id",'left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_expenses.payment_mode','left');
            $this->db->where('hms_expenses.paid_amount>0');
            $this->db->where('hms_expenses.is_deleted!=2');
            if(!empty($get['start_date']))
            {
              $this->db->where('hms_expenses.expenses_date >= "'.date('Y-m-d',strtotime($get['start_date'])).'"');
            }

            if(!empty($get['end_date']))
            {
              $this->db->where('hms_expenses.expenses_date<= "'.date('Y-m-d',strtotime($get['end_date'])).'"');   
            }

            if(!empty($get['branch_id']))
            {
              if(is_numeric($get['branch_id']) && $get['branch_id']>0)
              {
                 $this->db->where('hms_expenses.branch_id',$get['branch_id']);  
              } 
              else if($get['branch_id']=='all') 
              {
                 $this->db->where('hms_expenses.branch_id IN ('.$child_ids.')');  
              }  
            }

            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]) && is_numeric($get['employee']))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_expenses.created_by IN ('.$emp_ids.')');
            }
            $result_expense['expense_payment_mode'] = $this->db->get()->result(); 
            return $result_expense;  
          
        } 
    }

    public function branch_opd_collection_list($get="",$ids=[])
    {
        $new_payment_mode=array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            /*$this->db->select("hms_payment.id as p_id,hms_branch.branch_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode, hms_doctors.doctor_name,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name");
*/            
            $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_opd_booking.referral_doctor=0 THEN concat('Other ',hms_opd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode"); 
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_opd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (1,8)','left');

           
            //$this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id','left');

            /*$this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_payment.hospital_id','left');

 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); */
            
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            $this->db->where('hms_opd_booking.is_deleted!=2'); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
             
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (2)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->from('hms_payment');
            $new_payment_mode['opd_collection_list']= $this->db->get()->result(); 

            /* code from payment_mode by */

             $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id, hms_doctors.doctor_name, sum(hms_payment.debit) as to_debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_opd_booking.referral_doctor=0 THEN concat('Other ',hms_opd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode"); 
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_opd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (1,8)','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            $this->db->where('hms_opd_booking.is_deleted!=2'); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
             
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }

            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (2)');
            $this->db->where('hms_payment.debit>0'); 
             $this->db->group_by('hms_payment.pay_mode'); 
            $this->db->from('hms_payment');
            $new_payment_mode['payment_mode_list']= $this->db->get()->result(); 
            /* code from payment_mode by */

           //print '<pre>'; print_r($new_payment_mode);die;
           //echo $this->db->last_query(); exit; 
            return $new_payment_mode;
            
        } 
    }

    public function branch_billing_collection_list($get="",$ids=[])
    {
        $new_billing_array=array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            /*$this->db->select("hms_payment.id as p_id,hms_branch.branch_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode, hms_doctors.doctor_name");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id','left'); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); */

            $this->db->select("hms_payment.id as p_id,hms_branch.branch_name,hms_branch.id as branch_id,hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_opd_booking.referral_doctor=0 THEN concat('Other ',hms_opd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode"); 
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_opd_booking.referral_hospital','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (2,12)','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            $this->db->where('hms_opd_booking.is_deleted!=2'); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
            $this->db->where('hms_payment.created_date >= "'.date('Y-m-d',strtotime($get['start_date'])).'"');
            }

            if(!empty($get['end_date']))
            {
            $this->db->where('hms_payment.created_date <= "'.date('Y-m-d',strtotime($get['end_date'])).'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (4)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->from('hms_payment');
            $new_billing_array['billing_array'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 

            /* billing payment mode array */

             $this->db->select("hms_payment.id as p_id,hms_branch.branch_name,hms_branch.id as branch_id,hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_opd_booking.referral_doctor=0 THEN concat('Other ',hms_opd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode"); 
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_opd_booking.referral_hospital','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (2,12)','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            $this->db->where('hms_opd_booking.is_deleted!=2'); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
            $this->db->where('hms_payment.created_date >= "'.date('Y-m-d',strtotime($get['start_date'])).'"');
            }

            if(!empty($get['end_date']))
            {
            $this->db->where('hms_payment.created_date <= "'.date('Y-m-d',strtotime($get['end_date'])).'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (4)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->group_by('hms_payment.pay_mode'); 
            $this->db->from('hms_payment');
            $new_billing_array['billing_payment_mode_array'] = $this->db->get()->result(); 

            /* billing payment mode array */
           // print '<pre>'; print_r($new_billing_array);die;

            return $new_billing_array;
            
        } 
    }

    public function branch_medicine_collection_list($get="",$ids=[])
    {
        $new_medicine_array=array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            /*$this->db->select("hms_payment.id as p_id,hms_branch.branch_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode, hms_doctors.doctor_name");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id','left'); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left');*/


            $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id,hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_medicine_sale.refered_id=0 THEN concat('Other ',hms_medicine_sale.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id'); 
            $this->db->join('hms_medicine_sale','hms_medicine_sale.id=hms_payment.parent_id AND hms_medicine_sale.is_deleted!=2');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_medicine_sale.referral_hospital','left');
                 $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (6,10)','left');

            // $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.parent_id = hms_medicine_sale.id AND hms_branch_hospital_no.section_id IN(6,10)','left');

            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');

            //$this->db->where('hms_payment.doctor_id',0); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (3)'); 
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $new_medicine_array['medicine_collection_list'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 


            /* medicine sale collection list */

            $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id,hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_medicine_sale.refered_id=0 THEN concat('Other ',hms_medicine_sale.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_medicine_sale','hms_medicine_sale.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_medicine_sale.referral_hospital','left');
                 $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (6,10)','left');

            // $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.parent_id = hms_medicine_sale.id AND hms_branch_hospital_no.section_id IN(6,10)','left');

            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');

            //$this->db->where('hms_payment.doctor_id',0); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (3)'); 
            $this->db->where('hms_payment.debit>0');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_medicine_array['medicine_payment_mode_array'] = $this->db->get()->result(); 

            /* medicine sale collection list */
            return $new_medicine_array;
            
        } 
    }

    //ot branch collection
    public function branch_ot_collection_list($get="",$ids=[])
    {
         $new_array_ot=array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            /*$this->db->select("hms_payment.id as p_id,hms_branch.branch_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode, hms_doctors.doctor_name");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id','left'); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left');*/


            $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id,hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_operation_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_operation_booking.referral_doctor=0 THEN concat('Other ',hms_operation_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_operation_booking','hms_operation_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_operation_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_operation_booking.referral_hospital','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (16,15)','left');

            // $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.parent_id = hms_operation_booking.id AND hms_branch_hospital_no.section_id IN(16,15)','left');



            //$this->db->where('hms_payment.doctor_id',0); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (8)'); 
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $new_array_ot['ot_collection'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 

            /* payment mode ot */

              $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id,hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_operation_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_operation_booking.referral_doctor=0 THEN concat('Other ',hms_operation_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_operation_booking','hms_operation_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_operation_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_operation_booking.referral_hospital','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (16,15)','left');

             $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (8)'); 
            $this->db->where('hms_payment.debit>0');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_array_ot['ot_collection_payment_mode'] = $this->db->get()->result(); 


            /* payment mode ot */
            return $new_array_ot;
            
        } 
    }
    //ot branch collection


     //blood bank branch collection
    public function blood_bank_branch_collection_list($get="",$ids=[])
    {
         $new_array_blood_bank=array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            /*$this->db->select("hms_payment.id as p_id,hms_branch.branch_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode, hms_doctors.doctor_name");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id','left'); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left');*/


            $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id,hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_blood_patient_to_recipient.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)') ELSE concat('Dr. ',hms_doctors.doctor_name)END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_blood_patient_to_recipient','hms_blood_patient_to_recipient.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_blood_patient_to_recipient.doctor_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_blood_patient_to_recipient.hospital_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (17,18)','left');

            // $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.parent_id = hms_operation_booking.id AND hms_branch_hospital_no.section_id IN(16,15)','left');



            //$this->db->where('hms_payment.doctor_id',0); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (10)'); 
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $new_array_blood_bank['blood_bank_collection'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 

            /* payment mode ot */

               $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id,hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_blood_patient_to_recipient.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)') ELSE concat('Dr. ',hms_doctors.doctor_name)END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_blood_patient_to_recipient','hms_blood_patient_to_recipient.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_blood_patient_to_recipient.doctor_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_blood_patient_to_recipient.hospital_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (17,18)','left');

            // $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.parent_id = hms_operation_booking.id AND hms_branch_hospital_no.section_id IN(16,15)','left');



            //$this->db->where('hms_payment.doctor_id',0); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (10)'); 
            $this->db->where('hms_payment.debit>0');
             $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_array_blood_bank['blood_bank_coll_payment_mode'] = $this->db->get()->result(); 
             /* payment mode ot */
            return $new_array_blood_bank;
            
        } 
    }
    //blood bank branch collection

    public function branch_vaccination_collection_list($get="",$ids=[])
    {

         $new_vaccination_array=array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            /*$this->db->select("hms_payment.id as p_id,hms_branch.branch_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode, hms_doctors.doctor_name");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id','left'); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left');*/
            $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_vaccination_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_vaccination_sale.refered_id=0 THEN concat('Other ',hms_vaccination_sale.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left'); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_vaccination_sale','hms_vaccination_sale.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_vaccination_sale.refered_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_vaccination_sale.referral_hospital','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (5,13)','left'); 
            $this->db->where('hms_vaccination_sale.is_deleted!=2'); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (7)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->from('hms_payment');
            $new_vaccination_array['vaccination_collection'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 

            /* new vaccination array  */

             $this->db->select("hms_patient.patient_name,hms_branch.branch_name,hms_branch.id as branch_id, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_vaccination_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_vaccination_sale.refered_id=0 THEN concat('Other ',hms_vaccination_sale.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left'); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_vaccination_sale','hms_vaccination_sale.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_vaccination_sale.refered_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_vaccination_sale.referral_hospital','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (5,13)','left'); 
            $this->db->where('hms_vaccination_sale.is_deleted!=2'); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (7)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->from('hms_payment');
            $new_vaccination_array['vaccination_payment_mode_collection'] = $this->db->get()->result(); 

            /* new vaccination array */
            return $new_vaccination_array;
            
        } 
    }

      public function branch_medicine_return_collection_list($get="",$ids=[])
    {
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
           $this->db->select("hms_patient.patient_name,hms_branch.branch_name, hms_payment.type,hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_payment_mode.payment_mode as mode"); 
              $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
             $this->db->join('hms_medicine_return','hms_medicine_return.id=hms_payment.parent_id');
             $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left'); 
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            $this->db->where('hms_payment.type','3') ;
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            $this->db->where('hms_payment.vendor_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (3)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->from('hms_payment');
            $query = $this->db->get()->result(); 
            //print_r($query);die;
            //echo $this->db->last_query(); exit; 
            return $query;
            
        } 
    }

    public function medicine_branch_collection_list($get="",$ids=[])
    {
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            $this->db->select("hms_branch.branch_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode, hms_patient.patient_name,hms_payment_mode.payment_mode as mode");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id'); 
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            //$this->db->where('hms_payment.doctor_id',0); 
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            //$this->db->where('hms_payment.patient_id',0); 
            //$this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (3)'); 
            $this->db->from('hms_payment');
            $query = $this->db->get(); 
            return $query->result();

        } 
    }

    public function doctor_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        if(!empty($get))
        {  
            $this->db->select("hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_payment_mode.payment_mode as mode");
            $this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id'); 
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left');
            $this->db->where('hms_doctors.doctor_pay_type',2); 
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']); 
            $this->db->where('hms_payment.patient_id',0); 
            $this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            $this->db->where('hms_payment.section_id IN (2,3)'); 
            $this->db->from('hms_payment');
            $query = $this->db->get();  
            return $query->result();
        } 
    }

    public function self_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_payment_mode.payment_mode as mode"); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');  
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
             $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]) && is_numeric($get['employee']))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (2,3,4)'); 
            $this->db->from('hms_payment');
            $query = $this->db->get(); 
            //echo $this->db->last_query(); exit; 
            return $query->result();
        } 
    }

    public function self_opd_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users');
        $new_self_opd_array=array(); 
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_opd_booking.referral_doctor=0 THEN concat('Other ',hms_opd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name,
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_hospital','hms_hospital.id = hms_opd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (1,8)','left');

            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
             //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]) && is_numeric($get['employee']))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            //$this->db->where('hms_opd_booking.type',3);
            $this->db->where('hms_opd_booking.is_deleted != 2'); 
            $this->db->where('hms_payment.section_id IN (2)'); 
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $new_self_opd_array['self_opd_coll'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 

            /* self opd collection payment */

            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_opd_booking.referral_doctor=0 THEN concat('Other ',hms_opd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name,
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_hospital','hms_hospital.id = hms_opd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (1,8)','left');

            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
             //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            //$this->db->where('hms_opd_booking.type',3);
            $this->db->where('hms_opd_booking.is_deleted != 2'); 
            $this->db->where('hms_payment.section_id IN (2)'); 
            $this->db->where('hms_payment.debit>0');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_self_opd_array['self_opd_coll_payment_mode'] = $this->db->get()->result(); 

             /* self opd collection payment */

            return $new_self_opd_array;
        } 
    }



    public function self_billing_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        $new_self_billing= array();
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_opd_booking.referral_doctor=0 THEN concat('Other ',hms_opd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_opd_booking.referral_hospital','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (2,12)','left');
            //$this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.parent_id = hms_ipd_booking.id AND hms_branch_hospital_no.section_id=3','left');   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_opd_booking.type',3); //billing type in hms_opd_booking 3
            $this->db->where('hms_opd_booking.is_deleted != 2'); 
            $this->db->where('hms_payment.section_id IN (4)');  //billing section id 4
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $new_self_billing['self_bill_coll'] = $this->db->get()->result();  


            /* self bill coll payment mode  */

            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_opd_booking.referral_doctor=0 THEN concat('Other ',hms_opd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_opd_booking','hms_opd_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.attended_doctor','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_opd_booking.referral_hospital','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (2,12)','left');
            //$this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.parent_id = hms_ipd_booking.id AND hms_branch_hospital_no.section_id=3','left');   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
           /* if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_opd_booking.type',3); //billing type in hms_opd_booking 3
            $this->db->where('hms_opd_booking.is_deleted != 2'); 
            $this->db->where('hms_payment.section_id IN (4)');  //billing section id 4
            $this->db->where('hms_payment.debit>0');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_self_billing['self_bill_coll_payment_mode'] = $this->db->get()->result();  


              /* self bill coll payment mode  */

            return $new_self_billing;
        } 
    }
    
    public function self_medicine_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        $new_medicine_array=array();
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_medicine_sale.refered_id=0 THEN concat('Other ',hms_medicine_sale.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //,(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id'); 
            $this->db->join('hms_medicine_sale','hms_medicine_sale.id=hms_payment.parent_id AND hms_medicine_sale.is_deleted!=2');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_medicine_sale.referral_hospital','left');

            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (6,10)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (3)'); 
            $this->db->where('hms_medicine_sale.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $new_medicine_array['med_coll'] = $this->db->get()->result();  

            /* med coll payment mode */
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_medicine_sale.refered_id=0 THEN concat('Other ',hms_medicine_sale.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //,(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id'); 
            $this->db->join('hms_medicine_sale','hms_medicine_sale.id=hms_payment.parent_id AND hms_medicine_sale.is_deleted!=2');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_medicine_sale.referral_hospital','left');

            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (6,10)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
           /* if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (3)'); 
            $this->db->where('hms_medicine_sale.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_medicine_array['med_coll_pay_mode'] = $this->db->get()->result();  
            /* med coll payment mode */


            

            return $new_medicine_array;
        } 
    }

    //ot self collection
    public function self_ot_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        $new_self_ot_array=array();
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_operation_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_operation_booking.referral_doctor=0 THEN concat('Other ',hms_operation_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //,(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_operation_booking','hms_operation_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_operation_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_operation_booking.referral_hospital','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (16,15)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (8)'); 
            $this->db->where('hms_operation_booking.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            //$this->db->group_by('hms_payment.id');
            $new_self_ot_array['self_ot_coll'] = $this->db->get()->result(); 
            //echo  $this->db->last_query();die; 

            /* self ot collection payment_mode*/

             $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_operation_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_operation_booking.referral_doctor=0 THEN concat('Other ',hms_operation_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //,(CASE WHEN hms_medicine_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_operation_booking','hms_operation_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');

            $this->db->join('hms_doctors','hms_doctors.id=hms_operation_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_operation_booking.referral_hospital','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (16,15)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (8)'); 
            $this->db->where('hms_operation_booking.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            //$this->db->group_by('hms_payment.id');
            $new_self_ot_array['self_ot_coll_payment_mode'] = $this->db->get()->result(); 

             /* self ot collection payment_mode*/



            return $new_self_ot_array;
        } 
    }

    //ot self collection


    public function self_vaccination_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 

        $new_self_vaccination=array();
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_vaccination_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_vaccination_sale.refered_id=0 THEN concat('Other ',hms_vaccination_sale.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            
            //,(CASE WHEN hms_vaccination_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name

            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_vaccination_sale','hms_vaccination_sale.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_vaccination_sale.refered_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_vaccination_sale.referral_hospital','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (5,13)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
           /* if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (7)'); 
            $this->db->where('hms_vaccination_sale.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
           $new_self_vaccination['vaccine_coll'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 

           /* self payment mode */

           $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_vaccination_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_vaccination_sale.refered_id=0 THEN concat('Other ',hms_vaccination_sale.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            
            //,(CASE WHEN hms_vaccination_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name

            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_vaccination_sale','hms_vaccination_sale.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_vaccination_sale.refered_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_vaccination_sale.referral_hospital','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (5,13)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (7)'); 
            $this->db->where('hms_vaccination_sale.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
             $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
             $new_self_vaccination['vaccine_coll_payment_mode'] = $this->db->get()->result();

           /* self vaccination payment mode */
            return $new_self_vaccination;
        } 
    }

    /* self_blood_bank_collection_list*/

    public function self_blood_bank_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
       
        $new_self_blood=array();
        if(!empty($get))
        {  

            ///            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_blood_patient_to_recipient.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_blood_patient_to_recipient.doctor_id=0 THEN concat('Other ',hms_blood_patient_to_recipient.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 

            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_blood_patient_to_recipient.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)') ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            
            //,(CASE WHEN hms_vaccination_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name

            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_blood_patient_to_recipient','hms_blood_patient_to_recipient.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_blood_patient_to_recipient.doctor_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_blood_patient_to_recipient.hospital_id','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (17,18)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (10)'); 
            $this->db->where('hms_blood_patient_to_recipient.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $new_self_blood['self_blood_bank_collection'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 

           /* self payment mode */

           $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_blood_patient_to_recipient.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)') ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            
            //,(CASE WHEN hms_vaccination_sale.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name

            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_blood_patient_to_recipient','hms_blood_patient_to_recipient.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_blood_patient_to_recipient.doctor_id','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_blood_patient_to_recipient.hospital_id','left');

             $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (17,18)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
           /* if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/

            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id IN (10)'); 
            $this->db->where('hms_blood_patient_to_recipient.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $this->db->group_by('hms_payment.pay_mode');
            $new_self_blood['self_blood_bank_coll_payment_mode'] = $this->db->get()->result();
            //echo $this->db->last_query();die;

           /* self vaccination payment mode */
            return $new_self_blood;
        } 
    }


    /* self_blood_bank_collection_list */

    public function self_medicine_return_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_payment.type,hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_payment_mode.payment_mode as mode"); 
              $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
             $this->db->join('hms_medicine_return','hms_medicine_return.id=hms_payment.parent_id');
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            //$this->db->join('hms_doctors','hms_doctors.id=hms_medicine_sale.refered_id','left'); 
            $this->db->where('hms_payment.type','3') ;
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
              //user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_medicine_return.is_deleted != 2');
            $this->db->where('hms_payment.section_id IN (3)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->from('hms_payment');
            $query = $this->db->get()->result();  
          // print_r($query);die;
            return $query;
        } 
    }
    

    public function employee_list()
    {
        $users_data = $this->session->userdata('auth_users'); 
        $this->db->select('*');   
        $this->db->order_by('hms_employees.name','ASC');
        $this->db->where('is_deleted',0);  
        $this->db->where('branch_id',$users_data['parent_id']);  
        $query = $this->db->get('hms_employees');
        $result = $query->result(); 
        //echo $this->db->last_query(); 
        return $result; 
    }

    public function next_appotment_list($get=""){
          
            $this->db->select("hms_opd_booking.*,hms_patient.*,hms_doctors.doctor_name,hms_cities.city,hms_disease.disease as dise"); 
            $this->db->join('hms_patient','hms_patient.id=hms_opd_booking.patient_id','left');
             $this->db->join('hms_doctors','hms_doctors.id=hms_opd_booking.referral_doctor','left'); 
             $this->db->join('hms_cities','hms_cities.id=hms_patient.city_id','left');
              $this->db->join('hms_disease','hms_disease.id=hms_opd_booking.diseases','left');
             
            $this->db->where('hms_opd_booking.branch_id',$get['branch_id']); 
            // $this->db->where('hms_opd_booking.next_app_date!= ','0000-00-00',FALSE);
$this->db->where('hms_opd_booking.next_app_date!= ','0000-00-00');
             $this->db->where('hms_opd_booking.next_app_date!= ','1970-01-01');
           
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_opd_booking.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_opd_booking.created_date <= "'.$end_date.'"');
            }
            $this->db->where('hms_opd_booking.is_deleted',0); //billing type in hms_opd_booking 3
            $this->db->where('hms_opd_booking.status',0);  //billing section id 4
            $this->db->where('hms_opd_booking.type',2);  //billing section id 4
            $this->db->from('hms_opd_booking');
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_opd_booking.created_by IN ('.$emp_ids.')');
            }
            $query = $this->db->get();  
            //echo $this->db->last_query();die;
            return $query->result();
    }



    

    public function branch_source_name_list($get="",$ids=[])
    {
        $users_data = $this->session->userdata('auth_users'); 
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            $this->db->select("hms_patient_source.id as p_id,hms_patient_source.source,hms_branch.branch_name,(select count(id) from hms_opd_booking as total_opd where total_opd.source_from = hms_patient_source.id  AND total_opd.branch_id IN ('".$branch_id."')) as total,(select count(id) from hms_opd_booking as opd_appointment where opd_appointment.source_from = hms_patient_source.id AND opd_appointment.type=1 AND opd_appointment.branch_id IN ('".$branch_id."')) as total_enquiry, 
                   (select count(id) from hms_opd_booking as opd_booking where opd_booking.source_from = hms_patient_source.id AND opd_booking.type=2 AND opd_booking.branch_id IN ('".$branch_id."')) as total_booking, 
                   (select count(id) from hms_opd_booking as opd_billing where opd_billing.source_from = hms_patient_source.id AND opd_billing.type=3 AND opd_billing.branch_id IN ('".$branch_id."')) as total_billing");
            $this->db->join('hms_opd_booking','hms_patient_source.id=hms_opd_booking.source_from');
            $this->db->join('hms_branch','hms_branch.id=hms_patient_source.branch_id','left');
            $this->db->where('hms_patient_source.branch_id IN ('.$branch_id.')'); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_opd_booking.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_opd_booking.created_date <= "'.$end_date.'"');
            }
            $this->db->from('hms_patient_source');
            $query = $this->db->get(); 
            //echo $this->db->last_query(); exit; 
            return $query->result();
            
        } 
    }

    public function all_child_branch_source_report($get="",$ids=[])
    {
       $users_data = $this->session->userdata('auth_users'); 
        if(!empty($ids))
        {  
             $branch_id = implode(',', $ids);
            $this->db->select("hms_patient_source.source, 
                   (select count(id) from hms_opd_booking as total_opd where total_opd.source_from = hms_patient_source.id  AND total_opd.branch_id IN ('".$branch_id."')) as total,(select count(id) from hms_opd_booking as opd_appointment where opd_appointment.source_from = hms_patient_source.id AND opd_appointment.type=1 AND opd_appointment.branch_id IN ('".$branch_id."')) as total_enquiry, 
                   (select count(id) from hms_opd_booking as opd_booking where opd_booking.source_from = hms_patient_source.id AND opd_booking.type=2 AND opd_booking.branch_id IN ('".$branch_id."')) as total_booking, 
                   (select count(id) from hms_opd_booking as opd_billing where opd_billing.source_from = hms_patient_source.id AND opd_billing.type=3 AND opd_billing.branch_id IN ('".$branch_id."')) as total_billing"); 
            $this->db->join('hms_opd_booking','hms_patient_source.id=hms_opd_booking.source_from');
           // $this->db->where_in('hms_opd_booking.branch_id',$branch_id);
           $this->db->where('hms_patient_source.branch_id IN ('.$branch_id.')');    
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_opd_booking.created_date >= "'.$start_date.'"');
            }
            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_opd_booking.created_date <= "'.$end_date.'"');
            }
            $this->db->group_by('hms_patient_source.id');
            $this->db->from('hms_patient_source');
            $query = $this->db->get(); 
            //echo $this->db->last_query(); exit;
            return $query->result();
        }  
    }


   public function all_branch_users()
   {
        $users_data = $this->session->userdata('auth_users'); 
        $this->db->select("hms_users.*, hms_users.id as user_id, hms_employees.name"); 
        $this->db->join('hms_employees','hms_employees.id=hms_users.emp_id','left');
        $this->db->where('hms_users.parent_id',$users_data['parent_id']); 
        $this->db->where('hms_users.emp_id >','0');
        $this->db->from('hms_users');
        $query = $this->db->get();
        //echo $this->db->last_query(); exit;
        return $query->result();   
   }

   public function all_user_source_report($get="",$user_id='',$source_id='')
    {
       $users_data = $this->session->userdata('auth_users'); 
        if(!empty($user_id))
        {  
            $this->db->select("(select count(id) from hms_opd_booking as opd_appointment where opd_appointment.source_from = hms_patient_source.id AND opd_appointment.type=1 AND opd_appointment.appointment=1 AND opd_appointment.created_by='".$user_id."') as total_enquiry, 
                (select count(id) from hms_opd_booking as opd_booking where opd_booking.source_from = hms_patient_source.id AND opd_booking.type=2 AND opd_booking.appointment=1 AND opd_booking.created_by='".$user_id."') as total_booking"); 
            $this->db->join('hms_opd_booking','hms_patient_source.id=hms_opd_booking.source_from');
           // $this->db->where_in('hms_opd_booking.branch_id',$branch_id);
           $this->db->where('hms_patient_source.branch_id='.$users_data['parent_id']);
           $this->db->where('hms_opd_booking.source_from='.$source_id);    
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_opd_booking.created_date >= "'.$start_date.'"');
            }
            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_opd_booking.created_date <= "'.$end_date.'"');
            }
            $this->db->group_by('hms_patient_source.id');
            $this->db->from('hms_patient_source');
            $query = $this->db->get(); 
            //echo $this->db->last_query(); 
            return $query->result();
        }  
    }

    public function source_from_report_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        if(!empty($get))
        {  
             
        $this->db->select("hms_patient_source.id as source_id,hms_patient_source.source, 
               (select count(id) from hms_opd_booking as total_opd where total_opd.source_from = hms_patient_source.id  AND total_opd.branch_id = '".$users_data['parent_id']."' AND total_opd.appointment=1 ) as total,(select count(id) from hms_opd_booking as opd_appointment where opd_appointment.source_from = hms_patient_source.id AND opd_appointment.type=1 AND opd_appointment.appointment=1  AND opd_appointment.branch_id = '".$users_data['parent_id']."') as total_enquiry, 
                  (select count(id) from hms_opd_booking as opd_booking where opd_booking.source_from = hms_patient_source.id AND opd_booking.type=2  AND opd_booking.appointment=1  AND opd_booking.branch_id = '".$users_data['parent_id']."') as total_booking"); 
        
        $this->db->join('hms_opd_booking','hms_patient_source.id=hms_opd_booking.source_from');
        $this->db->join('hms_users','hms_users.id=hms_opd_booking.created_by','left');           
        $this->db->where('hms_opd_booking.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_opd_booking.created_date >= "'.$start_date.'"');
            }
            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_opd_booking.created_date <= "'.$end_date.'"');
            }
            $this->db->group_by('hms_patient_source.id');
            $this->db->from('hms_patient_source');
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_opd_booking.created_by IN ('.$emp_ids.')');
            }
            $query = $this->db->get(); 
            //echo $this->db->last_query(); exit;
            return $query->result();
        } 
    }

    //IPD Billing

    public function self_ipd_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        $new_self_ipd_coll=array();
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_ipd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_ipd_booking.   referral_doctor=0 THEN concat('Other ',hms_ipd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_ipd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_ipd_booking','hms_ipd_booking.id=hms_payment.parent_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_ipd_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_ipd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (3,7,9)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
                $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";
                $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (5)'); 
            $this->db->where('hms_ipd_booking.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->group_by('hms_payment.id');
            $this->db->from('hms_payment');
            $new_self_ipd_coll['ipd_coll'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); 

            /* payment coll_ipd */

            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_ipd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_ipd_booking.   referral_doctor=0 THEN concat('Other ',hms_ipd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //(CASE WHEN hms_ipd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_ipd_booking','hms_ipd_booking.id=hms_payment.parent_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_ipd_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_ipd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (3,7,9)','left');
   
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
                $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";
                $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (5)'); 
            $this->db->where('hms_ipd_booking.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            //$this->db->group_by('hms_payment.id');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_self_ipd_coll['ipd_coll_payment_mode'] = $this->db->get()->result(); 

            /* payment coll_ipd */
            return $new_self_ipd_coll;
        } 
    } 
    //ipd branch collection
    public function branch_ipd_collection_list($get="",$ids=[])
    {
         $new_ipd_array=array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            /*$this->db->select("hms_payment.id as p_id,hms_branch.branch_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode, hms_doctors.doctor_name");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id','left'); 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); */

            $this->db->select("hms_payment.id as p_id,hms_branch.branch_name,hms_branch.id as branch_id,hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_ipd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_ipd_booking.   referral_doctor=0 THEN concat('Other ',hms_ipd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode");
            //,(CASE WHEN hms_ipd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name 
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_ipd_booking','hms_ipd_booking.id=hms_payment.parent_id','left');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_ipd_booking.attend_doctor_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_ipd_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_ipd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (3,7,9)','left');

            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            if(!empty($get['start_date']))
            {
                $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";
                $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (5)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->from('hms_payment');
            $new_ipd_array['ipd_collection_list'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 


            /* ipd payment mode */

              $this->db->select("hms_payment.id as p_id,hms_branch.branch_name,hms_branch.id as branch_id,hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit)as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN hms_ipd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN hms_ipd_booking.   referral_doctor=0 THEN concat('Other ',hms_ipd_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode");
            //,(CASE WHEN hms_ipd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name 
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('hms_ipd_booking','hms_ipd_booking.id=hms_payment.parent_id','left');
            //$this->db->join('hms_doctors','hms_doctors.id=hms_ipd_booking.attend_doctor_id','left');
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=hms_ipd_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = hms_ipd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (3,7,9)','left');

            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            if(!empty($get['start_date']))
            {
                $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";
                $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (5)');
            $this->db->where('hms_payment.debit>0'); 
            $this->db->group_by('hms_payment.pay_mode'); 
            $this->db->from('hms_payment');
            $new_ipd_array['ipd_payment_mode_list'] = $this->db->get()->result(); 
            //echo $this->db->last_query(); exit; 
             return $new_ipd_array;
            /* ipd payment mode */
        
            
        } 
    }



    public function pathology_branch_collection_list($get="",$ids=[])
    {
         $new_path_array= array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            
            /*$this->db->select("hms_branch.branch_name, hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode");
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');  
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('path_test_booking','path_test_booking.id=hms_payment.parent_id');
            $this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');*/

            $this->db->select("hms_patient.patient_name, hms_branch.branch_name,hms_branch.id as branch_id, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN path_test_booking.referral_doctor=0 THEN concat('Other ',path_test_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');  
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('path_test_booking','path_test_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');   
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = path_test_booking.referral_hospital','left');

            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (4,11)','left');


            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')');   
            if(!empty($get['start_date']))
            {
            $start_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
             $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
            $end_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
               $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id',1); 
            $this->db->where('path_test_booking.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->order_by('hms_payment.id','DESC');
            $this->db->from('hms_payment');
            $new_path_array['pathalogy_collection'] = $this->db->get()->result(); 

            /* pathalogy payment mode list */
             $this->db->select("hms_patient.patient_name, hms_branch.branch_name,hms_branch.id as branch_id, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN path_test_booking.referral_doctor=0 THEN concat('Other ',path_test_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');  
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('path_test_booking','path_test_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');   
            $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = path_test_booking.referral_hospital','left');

            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (4,11)','left');


            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')');   
            if(!empty($get['start_date']))
            {
            $start_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
             $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
            $end_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
               $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            //user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id',1); 
            $this->db->where('path_test_booking.is_deleted != 2');
            $this->db->where('hms_payment.debit>0');
            $this->db->order_by('hms_payment.id','DESC');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_path_array['pathalogy_collection_payment_mode'] = $this->db->get()->result(); 
            /* pathalogy payment mode list */

            

            return $new_path_array;
        } 
    }

    public function pathology_doctor_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        if(!empty($get))
        {  
            $this->db->select("hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN path_test_booking.referral_doctor=0 THEN concat('Other ',path_test_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_name,hms_payment_mode.payment_mode as mode");
            //hms_doctors.doctor_name
            //$this->db->join('hms_doctors','hms_doctors.id=hms_payment.doctor_id');
            $this->db->join('path_test_booking','path_test_booking.id = hms_payment.parent_id AND is_deleted !=2','left');

            $this->db->join('hms_doctors','hms_doctors.id = path_test_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = path_test_booking.referral_hospital','left');
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->where('hms_doctors.doctor_pay_type',2); 
            $this->db->where('hms_payment.branch_id',$users_data['parent_id']); 
            $this->db->where('hms_payment.patient_id',0); 
            $this->db->where('hms_payment.parent_id',0); 
            if(!empty($get['start_date']))
            {
            $start_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
             $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
            $end_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
               $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id',1); 
            $this->db->order_by('hms_payment.id','DESC');
            $this->db->from('hms_payment');
            $query = $this->db->get();  
            return $query->result();
        } 
    }

    public function pathology_self_collection_list($get="")
    {
        $users_data = $this->session->userdata('auth_users'); 
        $new_self_path_array=array();
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN path_test_booking.referral_doctor=0 THEN concat('Other ',path_test_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('path_test_booking','path_test_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');   

            $this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = path_test_booking.referral_hospital','left');
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (4,11)','left');

            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
            $start_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
             $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
            $end_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
               $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id',1); 
            $this->db->where('path_test_booking.is_deleted != 2');
            $this->db->order_by('hms_payment.id','DESC');
            $this->db->from('hms_payment');
            $new_self_path_array['path_coll'] = $this->db->get()->result();  
            //echo $this->db->last_query();die;

            /* path payment coll */
             $this->db->select("hms_patient.patient_name, hms_doctors.doctor_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,hms_branch_hospital_no.reciept_prefix,hms_branch_hospital_no.reciept_suffix,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE (CASE WHEN path_test_booking.referral_doctor=0 THEN concat('Other ',path_test_booking.ref_by_other) ELSE concat('Dr. ',hms_doctors.doctor_name) END) END) as doctor_hospital_name,hms_payment_mode.payment_mode as mode"); 
            //,(CASE WHEN path_test_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id','left'); 
            $this->db->join('path_test_booking','path_test_booking.id=hms_payment.parent_id');
            //$this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');   

            $this->db->join('hms_doctors','hms_doctors.id=path_test_booking.referral_doctor','left');
            $this->db->join('hms_hospital','hms_hospital.id = path_test_booking.referral_hospital','left');
             $this->db->join('hms_payment_mode','hms_payment_mode.id=hms_payment.pay_mode','left'); 
            $this->db->join('hms_branch_hospital_no','hms_branch_hospital_no.payment_id = hms_payment.id AND hms_branch_hospital_no.section_id IN (4,11)','left');

            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
            $start_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
             $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
            $end_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
               $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
//user collection
            /*if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }*/
            $emp_ids='';
            if($users_data['emp_id']>0)
            {
                if($users_data['record_access']=='1')
                {
                    $emp_ids= $users_data['id'];
                }
            }
            elseif(!empty($get["employee"]))
            {
                $emp_ids=  $get["employee"];
            }
            if(isset($emp_ids) && !empty($emp_ids))
            { 
                $this->db->where('hms_payment.created_by IN ('.$emp_ids.')');
            }
            $this->db->where('hms_payment.section_id',1); 
            $this->db->where('path_test_booking.is_deleted != 2');
            $this->db->order_by('hms_payment.id','DESC');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_self_path_array['path_coll_pay_mode'] = $this->db->get()->result();  
            /* path payment coll */


            return  $new_self_path_array;
        } 
    }

    /* hospital collection report start */ 

    public function self_hospital_collection_list($get="")
    {

        $users_data = $this->session->userdata('auth_users'); 
        $new_array=array();
        if(!empty($get))
        {  
            $this->db->select("hms_patient.patient_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,

                (CASE WHEN hms_payment.section_id=1 THEN 'Pathology' WHEN hms_payment.section_id=2 THEN 'OPD' WHEN hms_payment.section_id=3 THEN 'Sale Medicine' WHEN hms_payment.section_id=4 THEN 'OPD Billing'  WHEN hms_payment.section_id=5 THEN 'IPD' WHEN hms_payment.section_id=7 THEN 'Vaccination' WHEN hms_payment.section_id=8 THEN 'OT' WHEN hms_payment.section_id=10 THEN 'Blood Bank'  ELSE '' END) as section_name,
                (CASE 
                    
                    WHEN hms_payment.section_id=1 THEN (CASE WHEN path_booking.referred_by =1 THEN concat(path_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',path_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=2 THEN (CASE WHEN opd_booking.referred_by =1 THEN 
                    concat(opd_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',opd_doctor.doctor_name) END)
                    
                    WHEN hms_payment.section_id=3 THEN (CASE WHEN sell_medicne.referred_by =1 THEN 

                    concat(medicine_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',medicine_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=4 THEN (CASE WHEN opd_billing.referred_by =1 THEN 
                    concat(billing_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',billing_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=5 THEN (CASE WHEN ipd_booking.referred_by =1 THEN

                    concat(ipd_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',ipd_doctor.doctor_name) END)



                    WHEN hms_payment.section_id=7 THEN (CASE WHEN vaccination_booking.referred_by =1 THEN concat(vaccination_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',vaccination_doctor.doctor_name) END)

                      WHEN hms_payment.section_id=8 THEN (CASE WHEN operation_booking.referred_by =1 THEN concat(operation_booking_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',operation_booking_doctor.doctor_name) END)

                      WHEN hms_payment.section_id=10 THEN (CASE WHEN recipient_booking.referred_by =1 THEN concat(recipient_booking_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',recipient_booking_doctor.doctor_name) END)

                       
                ELSE 'N/A'
                END
                ) as doctor_hospital_name,

                (CASE 
                    WHEN hms_payment.section_id=1 THEN path_hospital_no.reciept_prefix 
                    WHEN hms_payment.section_id=2 THEN opd_hospital_no.reciept_prefix 
                    WHEN hms_payment.section_id=3 THEN medicine_reciept_no.reciept_prefix
                    WHEN hms_payment.section_id=4 THEN billing_reciept_no.reciept_prefix 
                    WHEN hms_payment.section_id=5 THEN ipd_reciept_no.reciept_prefix
                    WHEN hms_payment.section_id=7 THEN vaccination_reciept_no.reciept_prefix  
                    WHEN hms_payment.section_id=8 THEN operation_booking_reciept_no.reciept_prefix  
                    WHEN hms_payment.section_id=10 THEN receipent_booking_reciept_no.reciept_prefix  

                    ELSE '' END) as reciept_prefix,

                 (CASE 
                    WHEN hms_payment.section_id=1 THEN path_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=2 THEN opd_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=3 THEN medicine_payment_mode.payment_mode
                    WHEN hms_payment.section_id=4 THEN billing_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=5 THEN ipd_payment_mode.payment_mode
                    WHEN hms_payment.section_id=7 THEN vaccination_payment_mode.payment_mode  
                    WHEN hms_payment.section_id=8 THEN operation_payment_mode.payment_mode  
                    WHEN hms_payment.section_id=10 THEN recepient_payment_mode.payment_mode  

                    ELSE '' END) as mode_name,


                (CASE 

                    WHEN hms_payment.section_id=1 THEN path_hospital_no.reciept_suffix 
                    WHEN hms_payment.section_id=2 THEN opd_hospital_no.reciept_suffix 
                    WHEN hms_payment.section_id=3 THEN medicine_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=4 THEN billing_reciept_no.reciept_suffix 
                    WHEN hms_payment.section_id=5 THEN ipd_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=7 THEN vaccination_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=8 THEN  operation_booking_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=10 THEN receipent_booking_reciept_no.reciept_suffix
                    ELSE '' END) as reciept_suffix
                "
                );

            //common 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id');
            //OPD
            $this->db->join('hms_opd_booking as opd_booking','opd_booking.id=hms_payment.parent_id AND opd_booking.is_deleted != 2 AND opd_booking.type = 2','left');
            $this->db->join('hms_doctors as opd_doctor','opd_doctor.id=opd_booking.referral_doctor','left');
            $this->db->join('hms_hospital as opd_hospital','opd_hospital.id = opd_booking.referral_hospital','left');
             /* get payment mode */
               //opd_payment_mode
             $this->db->join('hms_payment_mode as opd_payment_mode','opd_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            $this->db->join('hms_branch_hospital_no as opd_hospital_no','opd_hospital_no.payment_id = hms_payment.id AND opd_hospital_no.section_id IN (1,8)','left'); //8
            //OPD

            //Billing
            $this->db->join('hms_opd_booking as opd_billing','opd_billing.id=hms_payment.parent_id AND opd_billing.is_deleted != 2 AND opd_booking.type = 3','left');
            $this->db->join('hms_doctors billing_doctor','billing_doctor.id=opd_billing.referral_doctor','left');
            $this->db->join('hms_hospital billing_hospital','billing_hospital.id = opd_billing.referral_hospital','left');
             /* get payment mode */
               //billing_payment_mode
             $this->db->join('hms_payment_mode as billing_payment_mode','billing_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            //1 OPD , 2 OPD Billing,6 medicine sell
            $this->db->join('hms_branch_hospital_no as billing_reciept_no','billing_reciept_no.payment_id = hms_payment.id AND billing_reciept_no.section_id IN (2,12)','left'); //12
            //Billing

            
            //medicine
            $this->db->join('hms_medicine_sale as sell_medicne','sell_medicne.id=hms_payment.parent_id AND sell_medicne.is_deleted != 2','left');
            $this->db->join('hms_hospital as medicine_hospital','medicine_hospital.id = sell_medicne.referral_hospital','left');
            $this->db->join('hms_doctors as medicine_doctor','medicine_doctor.id=sell_medicne.refered_id','left');
             /* get payment mode */
               //medicine_payment_mode
             $this->db->join('hms_payment_mode as medicine_payment_mode','medicine_payment_mode.id = hms_payment.pay_mode','left');
             /* ipd_payment_mode */
            $this->db->join('hms_branch_hospital_no as medicine_reciept_no','medicine_reciept_no.payment_id = hms_payment.id AND medicine_reciept_no.section_id IN (6,10)','left'); // 10
            //medicine


            //ipd
            $this->db->join('hms_ipd_booking as ipd_booking','ipd_booking.id=hms_payment.parent_id AND ipd_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as ipd_hospital','ipd_hospital.id = ipd_booking.referral_hospital','left');
            /* get payment mode */
               //ipd_payment_mode
             $this->db->join('hms_payment_mode as ipd_payment_mode','ipd_payment_mode.id = hms_payment.pay_mode','left');
             /* ipd_payment_mode */
            $this->db->join('hms_doctors as ipd_doctor','ipd_doctor.id=ipd_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as ipd_reciept_no','ipd_reciept_no.payment_id = hms_payment.id AND ipd_reciept_no.section_id IN(3,9,7)','left'); // 9
            //ipd

            //pathology
            $this->db->join('path_test_booking as path_booking','path_booking.id=hms_payment.parent_id AND path_booking.is_deleted!= 2','left');

            $this->db->join('hms_hospital as path_hospital','path_hospital.id = path_booking.referral_hospital','left');
            $this->db->join('hms_doctors as path_doctor','path_doctor.id=path_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as path_hospital_no','path_hospital_no.payment_id = hms_payment.id AND path_hospital_no.section_id IN (4,11)','left'); //11

               /* get payment mode */
               //path_payment_mode
             $this->db->join('hms_payment_mode as path_payment_mode','path_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            //pathology

            //vaccination_booking
            $this->db->join('hms_vaccination_sale as vaccination_booking','vaccination_booking.id=hms_payment.parent_id AND vaccination_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as vaccination_hospital','vaccination_hospital.id = vaccination_booking.referral_hospital','left');
            $this->db->join('hms_doctors as vaccination_doctor','vaccination_doctor.id=vaccination_booking.refered_id','left');
            $this->db->join('hms_branch_hospital_no as vaccination_reciept_no','vaccination_reciept_no.payment_id = hms_payment.id AND vaccination_reciept_no.section_id IN (5,13)','left'); //13
                /* get payment mode */
                //vaccination_payment_mode
             $this->db->join('hms_payment_mode as vaccination_payment_mode','vaccination_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            //vaccination_booking


             /* operation booking */
            $this->db->join('hms_operation_booking as operation_booking','operation_booking.id=hms_payment.parent_id AND operation_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as operation_booking_hospital','operation_booking_hospital.id = operation_booking.referral_hospital','left');
            $this->db->join('hms_doctors as operation_booking_doctor','operation_booking_doctor.id=operation_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as operation_booking_reciept_no','operation_booking_reciept_no.payment_id = hms_payment.id AND operation_booking_reciept_no.section_id IN (15,16)','left'); //13
                /* get payment mode */
                //vaccination_payment_mode
             $this->db->join('hms_payment_mode as operation_payment_mode','operation_payment_mode.id = hms_payment.pay_mode','left');

             /* operation booking */


                /* blood bank booking */
            $this->db->join('hms_blood_patient_to_recipient as recipient_booking','recipient_booking.id=hms_payment.parent_id AND recipient_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as recipient_booking_hospital','recipient_booking_hospital.id = recipient_booking.hospital_id','left');
            $this->db->join('hms_doctors as recipient_booking_doctor','recipient_booking_doctor.id=recipient_booking.doctor_id','left');
            $this->db->join('hms_branch_hospital_no as receipent_booking_reciept_no','receipent_booking_reciept_no.payment_id = hms_payment.id AND receipent_booking_reciept_no.section_id IN (17,18)','left'); //13
                /* get payment mode */
             $this->db->join('hms_payment_mode as recepient_payment_mode','recepient_payment_mode.id = hms_payment.pay_mode','left');

             /* blood bank booking */


            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
             //user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (1,2,3,4,5,7,8,10)'); 
            //2 OPD,4 OPD Billing ,3 medicne sell,
            $this->db->where('hms_payment.debit>0');


            //$this->db->order_by('hms_payment.created_date','DESC');
            //$this->db->order_by('vaccination_reciept_no.created_date,path_hospital_no.created_date,medicine_reciept_no.created_date,billing_reciept_no.created_date,opd_hospital_no.created_date','DESC');
            $this->db->from('hms_payment');
            //$query= $this->db->get()->resutl();
            //echo $this->db->last_query(); die;
            $new_array['over_all_collection'] = $this->db->get()->result_array(); 

           //echo $this->db->last_query(); die;
           

           /////////////////////// /* code for payment mode */////////////////////////////

            $this->db->select("hms_patient.patient_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,

                (CASE WHEN hms_payment.section_id=1 THEN 'Pathology' WHEN hms_payment.section_id=2 THEN 'OPD' WHEN hms_payment.section_id=3 THEN 'Sale Medicine' WHEN hms_payment.section_id=4 THEN 'OPD Billing'  WHEN hms_payment.section_id=5 THEN 'IPD' WHEN hms_payment.section_id=7 THEN 'Vaccination' WHEN hms_payment.section_id=8 THEN 'OT' WHEN hms_payment.section_id=10 THEN 'Blood Bank' ELSE '' END) as section_name,
                (CASE 
                    
                    WHEN hms_payment.section_id=1 THEN (CASE WHEN path_booking.referred_by =1 THEN concat(path_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',path_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=2 THEN (CASE WHEN opd_booking.referred_by =1 THEN 
                    concat(opd_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',opd_doctor.doctor_name) END)
                    
                    WHEN hms_payment.section_id=3 THEN (CASE WHEN sell_medicne.referred_by =1 THEN 

                    concat(medicine_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',medicine_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=4 THEN (CASE WHEN opd_billing.referred_by =1 THEN 
                    concat(billing_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',billing_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=5 THEN (CASE WHEN ipd_booking.referred_by =1 THEN

                    concat(ipd_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',ipd_doctor.doctor_name) END)



                    WHEN hms_payment.section_id=7 THEN (CASE WHEN vaccination_booking.referred_by =1 THEN concat(vaccination_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',vaccination_doctor.doctor_name) END)

                      WHEN hms_payment.section_id=8 THEN (CASE WHEN operation_booking.referred_by =1 THEN concat(operation_booking_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',operation_booking_doctor.doctor_name) END)

                      WHEN hms_payment.section_id=10 THEN (CASE WHEN recipient_booking.referred_by =1 THEN concat(recipient_booking_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',recipient_booking_doctor.doctor_name) END)

                       
                ELSE 'N/A'
                END
                ) as doctor_hospital_name,

                (CASE 
                    WHEN hms_payment.section_id=1 THEN path_hospital_no.reciept_prefix 
                    WHEN hms_payment.section_id=2 THEN opd_hospital_no.reciept_prefix 
                    WHEN hms_payment.section_id=3 THEN medicine_reciept_no.reciept_prefix
                    WHEN hms_payment.section_id=4 THEN billing_reciept_no.reciept_prefix 
                    WHEN hms_payment.section_id=5 THEN ipd_reciept_no.reciept_prefix
                    WHEN hms_payment.section_id=7 THEN vaccination_reciept_no.reciept_prefix  
                    WHEN hms_payment.section_id=8 THEN operation_booking_reciept_no.reciept_prefix  
                    WHEN hms_payment.section_id=10 THEN receipent_booking_reciept_no.reciept_prefix  

                    ELSE '' END) as reciept_prefix,

                 (CASE 
                    WHEN hms_payment.section_id=1 THEN path_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=2 THEN opd_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=3 THEN medicine_payment_mode.payment_mode
                    WHEN hms_payment.section_id=4 THEN billing_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=5 THEN ipd_payment_mode.payment_mode
                    WHEN hms_payment.section_id=7 THEN vaccination_payment_mode.payment_mode  
                    WHEN hms_payment.section_id=8 THEN operation_payment_mode.payment_mode  
                    WHEN hms_payment.section_id=10 THEN recepient_payment_mode.payment_mode  

                    ELSE '' END) as mode_name,


                (CASE 

                    WHEN hms_payment.section_id=1 THEN path_hospital_no.reciept_suffix 
                    WHEN hms_payment.section_id=2 THEN opd_hospital_no.reciept_suffix 
                    WHEN hms_payment.section_id=3 THEN medicine_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=4 THEN billing_reciept_no.reciept_suffix 
                    WHEN hms_payment.section_id=5 THEN ipd_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=7 THEN vaccination_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=8 THEN  operation_booking_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=10 THEN receipent_booking_reciept_no.reciept_suffix
                    ELSE '' END) as reciept_suffix
                "
                );

            //common 
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id');
            //OPD
            $this->db->join('hms_opd_booking as opd_booking','opd_booking.id=hms_payment.parent_id AND opd_booking.is_deleted != 2 AND opd_booking.type = 2','left');
            $this->db->join('hms_doctors as opd_doctor','opd_doctor.id=opd_booking.referral_doctor','left');
            $this->db->join('hms_hospital as opd_hospital','opd_hospital.id = opd_booking.referral_hospital','left');
             /* get payment mode */
               //opd_payment_mode
             $this->db->join('hms_payment_mode as opd_payment_mode','opd_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            $this->db->join('hms_branch_hospital_no as opd_hospital_no','opd_hospital_no.payment_id = hms_payment.id AND opd_hospital_no.section_id IN (1,8)','left'); //8
            //OPD

            //Billing
            $this->db->join('hms_opd_booking as opd_billing','opd_billing.id=hms_payment.parent_id AND opd_billing.is_deleted != 2 AND opd_booking.type = 3','left');
            $this->db->join('hms_doctors billing_doctor','billing_doctor.id=opd_billing.referral_doctor','left');
            $this->db->join('hms_hospital billing_hospital','billing_hospital.id = opd_billing.referral_hospital','left');
             /* get payment mode */
               //billing_payment_mode
             $this->db->join('hms_payment_mode as billing_payment_mode','billing_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            //1 OPD , 2 OPD Billing,6 medicine sell
            $this->db->join('hms_branch_hospital_no as billing_reciept_no','billing_reciept_no.payment_id = hms_payment.id AND billing_reciept_no.section_id IN (2,12)','left'); //12
            //Billing

            
            //medicine
            $this->db->join('hms_medicine_sale as sell_medicne','sell_medicne.id=hms_payment.parent_id AND sell_medicne.is_deleted != 2','left');
            $this->db->join('hms_hospital as medicine_hospital','medicine_hospital.id = sell_medicne.referral_hospital','left');
            $this->db->join('hms_doctors as medicine_doctor','medicine_doctor.id=sell_medicne.refered_id','left');
             /* get payment mode */
               //medicine_payment_mode
             $this->db->join('hms_payment_mode as medicine_payment_mode','medicine_payment_mode.id = hms_payment.pay_mode','left');
             /* ipd_payment_mode */
            $this->db->join('hms_branch_hospital_no as medicine_reciept_no','medicine_reciept_no.payment_id = hms_payment.id AND medicine_reciept_no.section_id IN (6,10)','left'); // 10
            //medicine


            //ipd
            $this->db->join('hms_ipd_booking as ipd_booking','ipd_booking.id=hms_payment.parent_id AND ipd_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as ipd_hospital','ipd_hospital.id = ipd_booking.referral_hospital','left');
            /* get payment mode */
               //ipd_payment_mode
             $this->db->join('hms_payment_mode as ipd_payment_mode','ipd_payment_mode.id = hms_payment.pay_mode','left');
             /* ipd_payment_mode */
            $this->db->join('hms_doctors as ipd_doctor','ipd_doctor.id=ipd_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as ipd_reciept_no','ipd_reciept_no.payment_id = hms_payment.id AND ipd_reciept_no.section_id IN(3,9,7)','left'); // 9
            //ipd

            //pathology
            $this->db->join('path_test_booking as path_booking','path_booking.id=hms_payment.parent_id AND path_booking.is_deleted!= 2','left');

            $this->db->join('hms_hospital as path_hospital','path_hospital.id = path_booking.referral_hospital','left');
            $this->db->join('hms_doctors as path_doctor','path_doctor.id=path_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as path_hospital_no','path_hospital_no.payment_id = hms_payment.id AND path_hospital_no.section_id IN (4,11)','left'); //11

               /* get payment mode */
               //path_payment_mode
             $this->db->join('hms_payment_mode as path_payment_mode','path_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            //pathology

            //vaccination_booking
            $this->db->join('hms_vaccination_sale as vaccination_booking','vaccination_booking.id=hms_payment.parent_id AND vaccination_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as vaccination_hospital','vaccination_hospital.id = vaccination_booking.referral_hospital','left');
            $this->db->join('hms_doctors as vaccination_doctor','vaccination_doctor.id=vaccination_booking.refered_id','left');
            $this->db->join('hms_branch_hospital_no as vaccination_reciept_no','vaccination_reciept_no.payment_id = hms_payment.id AND vaccination_reciept_no.section_id IN (5,13)','left'); //13
                /* get payment mode */
                //vaccination_payment_mode
             $this->db->join('hms_payment_mode as vaccination_payment_mode','vaccination_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            //vaccination_booking


             /* operation booking */
            $this->db->join('hms_operation_booking as operation_booking','operation_booking.id=hms_payment.parent_id AND operation_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as operation_booking_hospital','operation_booking_hospital.id = operation_booking.referral_hospital','left');
            $this->db->join('hms_doctors as operation_booking_doctor','operation_booking_doctor.id=operation_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as operation_booking_reciept_no','operation_booking_reciept_no.payment_id = hms_payment.id AND operation_booking_reciept_no.section_id IN (15,16)','left'); //13
                /* get payment mode */
                //vaccination_payment_mode
             $this->db->join('hms_payment_mode as operation_payment_mode','operation_payment_mode.id = hms_payment.pay_mode','left');

             /* operation booking */


                /* blood bank booking */
            $this->db->join('hms_blood_patient_to_recipient as recipient_booking','recipient_booking.id=hms_payment.parent_id AND recipient_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as recipient_booking_hospital','recipient_booking_hospital.id = recipient_booking.hospital_id','left');
            $this->db->join('hms_doctors as recipient_booking_doctor','recipient_booking_doctor.id=recipient_booking.doctor_id','left');
            $this->db->join('hms_branch_hospital_no as receipent_booking_reciept_no','receipent_booking_reciept_no.payment_id = hms_payment.id AND receipent_booking_reciept_no.section_id IN (17,18)','left'); //13
                /* get payment mode */
             $this->db->join('hms_payment_mode as recepient_payment_mode','recepient_payment_mode.id = hms_payment.pay_mode','left');

             /* blood bank booking */


            $this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
             //user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (1,2,3,4,5,7,8,10)'); 
            //2 OPD,4 OPD Billing ,3 medicne sell,
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->where('hms_payment.debit>0');


            //$this->db->order_by('hms_payment.created_date','DESC');
            //$this->db->order_by('vaccination_reciept_no.created_date,path_hospital_no.created_date,medicine_reciept_no.created_date,billing_reciept_no.created_date,opd_hospital_no.created_date','DESC');
            $this->db->from('hms_payment');

            $new_array['over_all_collection_payment_mode'] = $this->db->get()->result_array(); 



             /////////////////////// /* code for payment mode */////////////////////////////

            return $new_array;
            // print '<pre>'; print_r($data);die;
        } 
    }

    /* hospital collection report end  */

    /* hospital sub branch report start */
    public function branch_hospital_collection_list($get="",$ids=[])
    {
        $new_branch_overall_array=array();
        if(!empty($ids))
        { 
            $branch_id = implode(',', $ids); 
            $users_data = $this->session->userdata('auth_users'); 
         

            //(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name

            $this->db->select("hms_branch.branch_name,hms_patient.patient_name, hms_payment.debit, hms_payment.created_date, hms_payment.pay_mode,

                (CASE WHEN hms_payment.section_id=1 THEN 'Pathology' WHEN hms_payment.section_id=2 THEN 'OPD' WHEN hms_payment.section_id=3 THEN 'Sale Medicine' WHEN hms_payment.section_id=4 THEN 'OPD Billing'  WHEN hms_payment.section_id=5 THEN 'IPD' WHEN hms_payment.section_id=7 THEN 'Vaccination' WHEN hms_payment.section_id=8 THEN 'OT' WHEN hms_payment.section_id=10 THEN 'Blood Bank'  ELSE '' END) as section_name,
                (CASE 
                    
                    WHEN hms_payment.section_id=1 THEN (CASE WHEN path_booking.referred_by =1 THEN concat(path_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',path_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=2 THEN (CASE WHEN opd_booking.referred_by =1 THEN 
                    concat(opd_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',opd_doctor.doctor_name) END)
                    
                    WHEN hms_payment.section_id=3 THEN (CASE WHEN sell_medicne.referred_by =1 THEN 

                    concat(medicine_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',medicine_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=4 THEN (CASE WHEN opd_billing.referred_by =1 THEN 
                    concat(billing_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',billing_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=5 THEN (CASE WHEN ipd_booking.referred_by =1 THEN

                    concat(ipd_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',ipd_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=7 THEN (CASE WHEN vaccination_booking.referred_by =1 THEN concat(vaccination_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',vaccination_doctor.doctor_name) END)

                      WHEN hms_payment.section_id=8 THEN (CASE WHEN operation_booking.referred_by =1 THEN concat(operation_booking_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',operation_booking_doctor.doctor_name) END)

                      WHEN hms_payment.section_id=10 THEN (CASE WHEN recipient_booking.referred_by =1 THEN concat(recipient_booking_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',recipient_booking_doctor.doctor_name) END)

                       
                ELSE 'N/A'
                END
                ) as doctor_hospital_name,

                (CASE 
                    WHEN hms_payment.section_id=1 THEN path_hospital_no.reciept_prefix 
                    WHEN hms_payment.section_id=2 THEN opd_hospital_no.reciept_prefix 
                    WHEN hms_payment.section_id=3 THEN medicine_reciept_no.reciept_prefix
                    WHEN hms_payment.section_id=4 THEN billing_reciept_no.reciept_prefix 
                    WHEN hms_payment.section_id=5 THEN ipd_reciept_no.reciept_prefix
                    WHEN hms_payment.section_id=7 THEN vaccination_reciept_no.reciept_prefix  
                    WHEN hms_payment.section_id=8 THEN operation_booking_reciept_no.reciept_prefix  
                    WHEN hms_payment.section_id=10 THEN receipent_booking_reciept_no.reciept_prefix   

                    ELSE '' END) as reciept_prefix,
                  (CASE 
                    WHEN hms_payment.section_id=1 THEN path_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=2 THEN opd_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=3 THEN medicine_payment_mode.payment_mode
                    WHEN hms_payment.section_id=4 THEN billing_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=5 THEN ipd_payment_mode.payment_mode
                    WHEN hms_payment.section_id=7 THEN vaccination_payment_mode.payment_mode  
                    WHEN hms_payment.section_id=8 THEN operation_payment_mode.payment_mode  
                    WHEN hms_payment.section_id=10 THEN recepient_payment_mode.payment_mode  

                    ELSE '' END) as mode_name,

                (CASE 

                    WHEN hms_payment.section_id=1 THEN path_hospital_no.reciept_suffix 
                    WHEN hms_payment.section_id=2 THEN opd_hospital_no.reciept_suffix 
                    WHEN hms_payment.section_id=3 THEN medicine_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=4 THEN billing_reciept_no.reciept_suffix 
                    WHEN hms_payment.section_id=5 THEN ipd_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=7 THEN vaccination_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=8 THEN  operation_booking_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=10 THEN receipent_booking_reciept_no.reciept_suffix
                    ELSE '' END) as reciept_suffix
                "
                );

            //common doctor_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id');
            //OPD
            $this->db->join('hms_opd_booking as opd_booking','opd_booking.id=hms_payment.parent_id and opd_booking.is_deleted != 2','left');
            /* get payment mode */
               //opd_payment_mode
             $this->db->join('hms_payment_mode as opd_payment_mode','opd_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            $this->db->join('hms_doctors as opd_doctor','opd_doctor.id=opd_booking.referral_doctor','left');
            $this->db->join('hms_hospital as opd_hospital','opd_hospital.id = opd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no as opd_hospital_no','opd_hospital_no.payment_id = hms_payment.id AND opd_hospital_no.section_id IN(1,8)','left');
            //OPD

            //Billing
            $this->db->join('hms_opd_booking as opd_billing','opd_billing.id=hms_payment.parent_id and opd_billing.is_deleted != 2','left');
            $this->db->join('hms_doctors billing_doctor','billing_doctor.id=opd_billing.referral_doctor','left');
            $this->db->join('hms_hospital billing_hospital','billing_hospital.id = opd_billing.referral_hospital','left');
              //billing_payment_mode
             $this->db->join('hms_payment_mode as billing_payment_mode','billing_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            //1 OPD , 2 OPD Billing,6 medicine sell
            $this->db->join('hms_branch_hospital_no as billing_reciept_no','billing_reciept_no.payment_id = hms_payment.id AND billing_reciept_no.section_id IN(2,12)','left');
            //Billing

            
            //medicine
            $this->db->join('hms_medicine_sale as sell_medicne','sell_medicne.id=hms_payment.parent_id AND sell_medicne.is_deleted != 2','left');
             /* get payment mode */
               //medicine_payment_mode
             $this->db->join('hms_payment_mode as medicine_payment_mode','medicine_payment_mode.id = hms_payment.pay_mode','left');
             /* ipd_payment_mode */

            $this->db->join('hms_hospital as medicine_hospital','medicine_hospital.id = sell_medicne.referral_hospital','left');
            $this->db->join('hms_doctors as medicine_doctor','medicine_doctor.id=sell_medicne.refered_id','left');
            $this->db->join('hms_branch_hospital_no as medicine_reciept_no','medicine_reciept_no.payment_id = hms_payment.id AND medicine_reciept_no.section_id IN(6,10)','left');
            //medicine


            //ipd
            $this->db->join('hms_ipd_booking as ipd_booking','ipd_booking.id=hms_payment.parent_id AND ipd_booking.is_deleted != 2','left');

            $this->db->join('hms_hospital as ipd_hospital','ipd_hospital.id = ipd_booking.referral_hospital','left');
             /* get payment mode */
               //ipd_payment_mode
             $this->db->join('hms_payment_mode as ipd_payment_mode','ipd_payment_mode.id = hms_payment.pay_mode','left');
             /* ipd_payment_mode */
            $this->db->join('hms_doctors as ipd_doctor','ipd_doctor.id=ipd_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as ipd_reciept_no','ipd_reciept_no.payment_id = hms_payment.id AND ipd_reciept_no.section_id IN(3,9)','left');
            //ipd

            //pathology
            $this->db->join('path_test_booking as path_booking','path_booking.id=hms_payment.parent_id AND path_booking.is_deleted!= 2','left');

            $this->db->join('hms_hospital as path_hospital','path_hospital.id = path_booking.referral_hospital','left');
            $this->db->join('hms_doctors as path_doctor','path_doctor.id=path_booking.referral_doctor','left');
                 /* get payment mode */
               //path_payment_mode
             $this->db->join('hms_payment_mode as path_payment_mode','path_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            $this->db->join('hms_branch_hospital_no as path_hospital_no','path_hospital_no.payment_id = hms_payment.id AND path_hospital_no.section_id IN(4,11)','left');
            //pathology

            //vaccination_booking

                /* get payment mode */
                //vaccination_payment_mode
                $this->db->join('hms_payment_mode as vaccination_payment_mode','vaccination_payment_mode.id = hms_payment.pay_mode','left');
                /* get payment mode */

            $this->db->join('hms_vaccination_sale as vaccination_booking','vaccination_booking.id=hms_payment.parent_id AND vaccination_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as vaccination_hospital','vaccination_hospital.id = vaccination_booking.referral_hospital','left');
            $this->db->join('hms_doctors as vaccination_doctor','vaccination_doctor.id=vaccination_booking.refered_id','left');
            $this->db->join('hms_branch_hospital_no as vaccination_reciept_no','vaccination_reciept_no.payment_id = hms_payment.id AND vaccination_reciept_no.section_id IN(5,13)','left');
            //vaccination_booking


             /* operation booking */
            $this->db->join('hms_operation_booking as operation_booking','operation_booking.id=hms_payment.parent_id AND operation_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as operation_booking_hospital','operation_booking_hospital.id = operation_booking.referral_hospital','left');
            $this->db->join('hms_doctors as operation_booking_doctor','operation_booking_doctor.id=operation_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as operation_booking_reciept_no','operation_booking_reciept_no.payment_id = hms_payment.id AND operation_booking_reciept_no.section_id IN (15,16)','left'); //13
                /* get payment mode */
                //vaccination_payment_mode
             $this->db->join('hms_payment_mode as operation_payment_mode','operation_payment_mode.id = hms_payment.pay_mode','left');

             /* operation booking */


                /* blood bank booking */
            $this->db->join('hms_blood_patient_to_recipient as recipient_booking','recipient_booking.id=hms_payment.parent_id AND recipient_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as recipient_booking_hospital','recipient_booking_hospital.id = recipient_booking.hospital_id','left');
            $this->db->join('hms_doctors as recipient_booking_doctor','recipient_booking_doctor.id=recipient_booking.doctor_id','left');
            $this->db->join('hms_branch_hospital_no as receipent_booking_reciept_no','receipent_booking_reciept_no.payment_id = hms_payment.id AND receipent_booking_reciept_no.section_id IN (17,18)','left'); //13
                /* get payment mode */
             $this->db->join('hms_payment_mode as recepient_payment_mode','recepient_payment_mode.id = hms_payment.pay_mode','left');

             /* blood bank booking */





            //$this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
             //user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (1,2,3,4,5,7,8,10)'); 
            //2 OPD,4 OPD Billing ,3 medicne sell,
            $this->db->where('hms_payment.debit>0');
            $this->db->from('hms_payment');
            $new_branch_overall_array['over_all_branch_data'] = $this->db->get()->result_array(); 
            //echo $this->db->last_query(); exit; 

            /////////////////////// /* code for payment mode */////////////////////////////

            $users_data = $this->session->userdata('auth_users'); 
         

            //(CASE WHEN hms_opd_booking.referred_by =1 THEN concat(hms_hospital.hospital_name,' (Hospital)')  ELSE concat('Dr. ',hms_doctors.doctor_name) END) as doctor_hospital_name

            $this->db->select("hms_branch.branch_name,hms_patient.patient_name, sum(hms_payment.debit) as tot_debit, hms_payment.created_date, hms_payment.pay_mode,

                (CASE WHEN hms_payment.section_id=1 THEN 'Pathology' WHEN hms_payment.section_id=2 THEN 'OPD' WHEN hms_payment.section_id=3 THEN 'Sale Medicine' WHEN hms_payment.section_id=4 THEN 'OPD Billing'  WHEN hms_payment.section_id=5 THEN 'IPD' WHEN hms_payment.section_id=7 THEN 'Vaccination' WHEN hms_payment.section_id=8 THEN 'OT' WHEN hms_payment.section_id=10 THEN 'Blood Bank' ELSE '' END) as section_name,
                (CASE 
                    
                    WHEN hms_payment.section_id=1 THEN (CASE WHEN path_booking.referred_by =1 THEN concat(path_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',path_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=2 THEN (CASE WHEN opd_booking.referred_by =1 THEN 
                    concat(opd_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',opd_doctor.doctor_name) END)
                    
                    WHEN hms_payment.section_id=3 THEN (CASE WHEN sell_medicne.referred_by =1 THEN 

                    concat(medicine_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',medicine_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=4 THEN (CASE WHEN opd_billing.referred_by =1 THEN 
                    concat(billing_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',billing_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=5 THEN (CASE WHEN ipd_booking.referred_by =1 THEN

                    concat(ipd_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',ipd_doctor.doctor_name) END)

                    WHEN hms_payment.section_id=7 THEN (CASE WHEN vaccination_booking.referred_by =1 THEN concat(vaccination_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',vaccination_doctor.doctor_name) END)

                     WHEN hms_payment.section_id=8 THEN (CASE WHEN operation_booking.referred_by =1 THEN concat(operation_booking_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',operation_booking_doctor.doctor_name) END)

                      WHEN hms_payment.section_id=10 THEN (CASE WHEN recipient_booking.referred_by =1 THEN concat(recipient_booking_hospital.hospital_name,' (Hospital)') ELSE  concat('Dr. ',recipient_booking_doctor.doctor_name) END)

                       
                ELSE 'N/A'
                END
                ) as doctor_hospital_name,

                (CASE 
                    WHEN hms_payment.section_id=1 THEN path_hospital_no.reciept_prefix 
                    WHEN hms_payment.section_id=2 THEN opd_hospital_no.reciept_prefix 
                    WHEN hms_payment.section_id=3 THEN medicine_reciept_no.reciept_prefix
                    WHEN hms_payment.section_id=4 THEN billing_reciept_no.reciept_prefix 
                    WHEN hms_payment.section_id=5 THEN ipd_reciept_no.reciept_prefix
                    WHEN hms_payment.section_id=7 THEN vaccination_reciept_no.reciept_prefix  
                    WHEN hms_payment.section_id=8 THEN operation_booking_reciept_no.reciept_prefix  
                    WHEN hms_payment.section_id=10 THEN receipent_booking_reciept_no.reciept_prefix     

                    ELSE '' END) as reciept_prefix,
                  (CASE 
                    WHEN hms_payment.section_id=1 THEN path_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=2 THEN opd_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=3 THEN medicine_payment_mode.payment_mode
                    WHEN hms_payment.section_id=4 THEN billing_payment_mode.payment_mode 
                    WHEN hms_payment.section_id=5 THEN ipd_payment_mode.payment_mode
                    WHEN hms_payment.section_id=7 THEN vaccination_payment_mode.payment_mode  
                     WHEN hms_payment.section_id=8 THEN operation_payment_mode.payment_mode  
                    WHEN hms_payment.section_id=10 THEN recepient_payment_mode.payment_mode 

                    ELSE '' END) as mode_name,

                (CASE 

                    WHEN hms_payment.section_id=1 THEN path_hospital_no.reciept_suffix 
                    WHEN hms_payment.section_id=2 THEN opd_hospital_no.reciept_suffix 
                    WHEN hms_payment.section_id=3 THEN medicine_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=4 THEN billing_reciept_no.reciept_suffix 
                    WHEN hms_payment.section_id=5 THEN ipd_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=7 THEN vaccination_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=8 THEN  operation_booking_reciept_no.reciept_suffix
                    WHEN hms_payment.section_id=10 THEN receipent_booking_reciept_no.reciept_suffix
                    ELSE '' END) as reciept_suffix
                "
                );

            //common doctor_name
            $this->db->join('hms_branch','hms_branch.id=hms_payment.branch_id','left');
            $this->db->join('hms_patient','hms_patient.id=hms_payment.patient_id');
            //OPD
            $this->db->join('hms_opd_booking as opd_booking','opd_booking.id=hms_payment.parent_id and opd_booking.is_deleted != 2','left');
            /* get payment mode */
               //opd_payment_mode
             $this->db->join('hms_payment_mode as opd_payment_mode','opd_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            $this->db->join('hms_doctors as opd_doctor','opd_doctor.id=opd_booking.referral_doctor','left');
            $this->db->join('hms_hospital as opd_hospital','opd_hospital.id = opd_booking.referral_hospital','left');
            $this->db->join('hms_branch_hospital_no as opd_hospital_no','opd_hospital_no.payment_id = hms_payment.id AND opd_hospital_no.section_id IN(1,8)','left');
            //OPD

            //Billing
            $this->db->join('hms_opd_booking as opd_billing','opd_billing.id=hms_payment.parent_id and opd_billing.is_deleted != 2','left');
            $this->db->join('hms_doctors billing_doctor','billing_doctor.id=opd_billing.referral_doctor','left');
            $this->db->join('hms_hospital billing_hospital','billing_hospital.id = opd_billing.referral_hospital','left');
              //billing_payment_mode
             $this->db->join('hms_payment_mode as billing_payment_mode','billing_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            //1 OPD , 2 OPD Billing,6 medicine sell
            $this->db->join('hms_branch_hospital_no as billing_reciept_no','billing_reciept_no.payment_id = hms_payment.id AND billing_reciept_no.section_id IN(2,12)','left');
            //Billing

            
            //medicine
            $this->db->join('hms_medicine_sale as sell_medicne','sell_medicne.id=hms_payment.parent_id AND sell_medicne.is_deleted != 2','left');
             /* get payment mode */
               //medicine_payment_mode
             $this->db->join('hms_payment_mode as medicine_payment_mode','medicine_payment_mode.id = hms_payment.pay_mode','left');
             /* ipd_payment_mode */

            $this->db->join('hms_hospital as medicine_hospital','medicine_hospital.id = sell_medicne.referral_hospital','left');
            $this->db->join('hms_doctors as medicine_doctor','medicine_doctor.id=sell_medicne.refered_id','left');
            $this->db->join('hms_branch_hospital_no as medicine_reciept_no','medicine_reciept_no.payment_id = hms_payment.id AND medicine_reciept_no.section_id IN(6,10)','left');
            //medicine


            //ipd
            $this->db->join('hms_ipd_booking as ipd_booking','ipd_booking.id=hms_payment.parent_id AND ipd_booking.is_deleted != 2','left');

            $this->db->join('hms_hospital as ipd_hospital','ipd_hospital.id = ipd_booking.referral_hospital','left');
             /* get payment mode */
               //ipd_payment_mode
             $this->db->join('hms_payment_mode as ipd_payment_mode','ipd_payment_mode.id = hms_payment.pay_mode','left');
             /* ipd_payment_mode */
            $this->db->join('hms_doctors as ipd_doctor','ipd_doctor.id=ipd_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as ipd_reciept_no','ipd_reciept_no.payment_id = hms_payment.id AND ipd_reciept_no.section_id IN(3,9)','left');
            //ipd

            //pathology
            $this->db->join('path_test_booking as path_booking','path_booking.id=hms_payment.parent_id AND path_booking.is_deleted!= 2','left');

            $this->db->join('hms_hospital as path_hospital','path_hospital.id = path_booking.referral_hospital','left');
            $this->db->join('hms_doctors as path_doctor','path_doctor.id=path_booking.referral_doctor','left');
                 /* get payment mode */
               //path_payment_mode
             $this->db->join('hms_payment_mode as path_payment_mode','path_payment_mode.id = hms_payment.pay_mode','left');
             /* get payment mode */
            $this->db->join('hms_branch_hospital_no as path_hospital_no','path_hospital_no.payment_id = hms_payment.id AND path_hospital_no.section_id IN(4,11)','left');
            //pathology

            //vaccination_booking

                /* get payment mode */
                //vaccination_payment_mode
                $this->db->join('hms_payment_mode as vaccination_payment_mode','vaccination_payment_mode.id = hms_payment.pay_mode','left');
                /* get payment mode */

            $this->db->join('hms_vaccination_sale as vaccination_booking','vaccination_booking.id=hms_payment.parent_id AND vaccination_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as vaccination_hospital','vaccination_hospital.id = vaccination_booking.referral_hospital','left');
            $this->db->join('hms_doctors as vaccination_doctor','vaccination_doctor.id=vaccination_booking.refered_id','left');
            $this->db->join('hms_branch_hospital_no as vaccination_reciept_no','vaccination_reciept_no.payment_id = hms_payment.id AND vaccination_reciept_no.section_id IN(5,13)','left');
            //vaccination_booking


               /* operation booking */
            $this->db->join('hms_operation_booking as operation_booking','operation_booking.id=hms_payment.parent_id AND operation_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as operation_booking_hospital','operation_booking_hospital.id = operation_booking.referral_hospital','left');
            $this->db->join('hms_doctors as operation_booking_doctor','operation_booking_doctor.id=operation_booking.referral_doctor','left');
            $this->db->join('hms_branch_hospital_no as operation_booking_reciept_no','operation_booking_reciept_no.payment_id = hms_payment.id AND operation_booking_reciept_no.section_id IN (15,16)','left'); //13
                /* get payment mode */
                //vaccination_payment_mode
             $this->db->join('hms_payment_mode as operation_payment_mode','operation_payment_mode.id = hms_payment.pay_mode','left');

             /* operation booking */


                /* blood bank booking */
            $this->db->join('hms_blood_patient_to_recipient as recipient_booking','recipient_booking.id=hms_payment.parent_id AND recipient_booking.is_deleted != 2','left');
            $this->db->join('hms_hospital as recipient_booking_hospital','recipient_booking_hospital.id = recipient_booking.hospital_id','left');
            $this->db->join('hms_doctors as recipient_booking_doctor','recipient_booking_doctor.id=recipient_booking.doctor_id','left');
            $this->db->join('hms_branch_hospital_no as receipent_booking_reciept_no','receipent_booking_reciept_no.payment_id = hms_payment.id AND receipent_booking_reciept_no.section_id IN (17,18)','left'); //13
                /* get payment mode */
             $this->db->join('hms_payment_mode as recepient_payment_mode','recepient_payment_mode.id = hms_payment.pay_mode','left');

             /* blood bank booking */

            //$this->db->where('hms_payment.branch_id',$users_data['parent_id']);   
            $this->db->where('hms_payment.branch_id IN ('.$branch_id.')'); 
            if(!empty($get['start_date']))
            {
               $start_date=date('Y-m-d',strtotime($get['start_date']))." 00:00:00";

               $this->db->where('hms_payment.created_date >= "'.$start_date.'"');
            }

            if(!empty($get['end_date']))
            {
                $end_date=date('Y-m-d',strtotime($get['end_date']))." 23:59:59";
                $this->db->where('hms_payment.created_date <= "'.$end_date.'"');
            }
             //user collection
            if(!empty($get['employee']))
            {
                $this->db->where('hms_payment.created_by = "'.$get['employee'].'"');
            }
            $this->db->where('hms_payment.section_id IN (1,2,3,4,5,7,8,10)'); 
            //2 OPD,4 OPD Billing ,3 medicne sell,
            $this->db->where('hms_payment.debit>0');
            $this->db->group_by('hms_payment.pay_mode');
            $this->db->from('hms_payment');
            $new_branch_overall_array['over_all_branch_data_payment_mode'] = $this->db->get()->result_array(); 

            /////////////////////// /* code for payment mode */////////////////////////////


            return $new_branch_overall_array;

            /* ends */
        } 
    }

    /* hospital sub branch report end */

    //PROFIT LOSS
    
    function medicine_profit_loss_report_list($get="")
    {
        $user_data = $this->session->userdata('auth_users');
        $purchase_where = '';
        $sale_where='';
        if(!empty($get['start_date']))
        {
            $from_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
            $to_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
        }
        
    

$sql1 = 'SELECT  `hms_medicine_entry`.`medicine_name` ,`hms_medicine_stock`.`created_date` as `stock_created_date`,`hms_medicine_stock`.`batch_no`, 

(select SUM(qtk.credit) from hms_medicine_stock as qtk where qtk.m_id=hms_medicine_entry.id AND `qtk`.`created_date` >= "'.$from_date.'" AND `qtk`.`created_date` <= "'.$to_date.'" AND qtk.branch_id='.$user_data['parent_id'].' AND `qtk`.is_deleted=0 AND qtk.type=3 AND qtk.batch_no=hms_medicine_stock.batch_no  GROUP BY `qtk`.`batch_no`) as sale_total_qty,

(select (SUM(stk.total_amount-((stk.total_amount*stk.sgst/100)+(stk.total_amount*stk.cgst/100)+(stk.total_amount*stk.igst/100))))            from hms_medicine_stock as stk where stk.m_id=hms_medicine_entry.id AND `stk`.`created_date` >= "'.$from_date.'" AND `stk`.`created_date` <= "'.$to_date.'" AND stk.branch_id='.$user_data['parent_id'].' AND stk.type=3  AND `stk`.`is_deleted`=0  AND stk.batch_no=hms_medicine_stock.batch_no  GROUP BY `stk`.`batch_no`  ) as sale_total_price,

(select ((SELECT sell.discount_percent from hms_medicine_sale as sell WHERE sell.id=stk.parent_id AND stk.type=3 AND stk.branch_id='.$user_data['parent_id'].')*( stk.total_amount-((stk.total_amount*stk.sgst/100)+(stk.total_amount*stk.cgst/100)+(stk.total_amount*stk.igst/100)))/100) from hms_medicine_stock as stk where stk.m_id=hms_medicine_entry.id AND `stk`.`created_date` >= "'.$from_date.'"  AND `stk`.`created_date` <= "'.$to_date.'" AND stk.branch_id='.$user_data['parent_id'].' AND stk.type=3 AND `stk`.`is_deleted`=0 AND stk.batch_no=hms_medicine_stock.batch_no GROUP BY `stk`.`batch_no` ) as sale_discount_amount
,


(select SUM((ptk.purchase_rate/ptk.conversion)-(((ptk.purchase_rate/ptk.conversion)*ptk.sgst/100)+((ptk.purchase_rate/ptk.conversion)*ptk.cgst/100)+((ptk.purchase_rate/ptk.conversion)*ptk.igst/100))) from hms_medicine_stock as ptk where ptk.m_id=hms_medicine_entry.id AND `ptk`.`created_date` >= "'.$from_date.'" AND `ptk`.`created_date` <= "'.$to_date.'" AND ptk.branch_id='.$user_data['parent_id'].' AND ptk.type=1 AND `ptk`.`is_deleted`=0  AND ptk.batch_no=hms_medicine_stock.batch_no GROUP BY `ptk`.`batch_no`  ) as purchase_total_price,

(select ((SELECT rett.discount_percent from hms_medicine_purchase as rett WHERE rett.id=ptk.parent_id AND ptk.type=1 AND ptk.branch_id='.$user_data['parent_id'].')*( ptk.purchase_rate-((ptk.purchase_rate*ptk.sgst/100)+(ptk.purchase_rate*ptk.cgst/100)+(ptk.purchase_rate*ptk.igst/100)))/100) from hms_medicine_stock as ptk where ptk.m_id=hms_medicine_entry.id AND `ptk`.`created_date` >= "'.$from_date.'"  AND `ptk`.`created_date` <= "'.$to_date.'" AND ptk.branch_id='.$user_data['parent_id'].' AND ptk.type=1 AND `ptk`.`is_deleted`=0 AND ptk.batch_no=hms_medicine_stock.batch_no GROUP BY `ptk`.`batch_no` ) as purchase_discount_amount,


(select (SUM(rtk.total_amount/(1+(rtk.sgst+rtk.cgst+rtk.igst)/100))-(((SELECT rett.discount_percent from hms_medicine_sale_return as rett WHERE rett.id=rtk.parent_id and rtk.m_id=hms_medicine_entry.id AND rtk.type=4 AND rtk.branch_id='.$user_data['parent_id'].')*(rtk.total_amount))/100))         from hms_medicine_stock as rtk where rtk.m_id=hms_medicine_entry.id AND `rtk`.`created_date` >= "'.$from_date.'" AND `rtk`.`created_date` <= "'.$to_date.'" AND rtk.branch_id='.$user_data['parent_id'].' AND rtk.type=4 AND `rtk`.`is_deleted`=0 AND rtk.batch_no=hms_medicine_stock.batch_no GROUP BY `rtk`.`batch_no`  ) as sale_return_total_price,



(hms_medicine_entry.purchase_rate/hms_medicine_entry.conversion) as opening_stock_price




FROM `hms_medicine_entry` LEFT JOIN `hms_medicine_stock` ON `hms_medicine_stock`.`m_id` = `hms_medicine_entry`.`id` AND `hms_medicine_stock`.`is_deleted`=0


 WHERE `hms_medicine_entry`.`is_deleted` = "0" AND `hms_medicine_entry`.`branch_id` ='.$user_data['parent_id'].' AND `hms_medicine_stock`.`created_date` >= "'.$from_date.'" AND `hms_medicine_stock`.`created_date` <= "'.$to_date.'" AND `hms_medicine_stock`.`is_deleted`=0  AND `hms_medicine_stock`.`type`=3 GROUP BY `hms_medicine_stock`.`batch_no`, `hms_medicine_entry`.`id` ORDER BY `hms_medicine_stock`.`id` DESC, `hms_medicine_entry`.`medicine_name` DESC';

 $sql =  $this->db->query($sql1); 
//echo $this->db->last_query();die; 
//SUM(stk.total_amount/(1+(stk.sgst+stk.cgst+stk.igst)/100))
//SUM(ptk.purchase_rate/conversion (1+(ptk.sgst+ptk.cgst+ptk.igst)/100))
//SUM(rtk.total_amount/(1+(rtk.sgst+rtk.cgst+rtk.igst)/100)) 
return $sql->result_array();

/*
-(((hms_medicine_entry.purchase_rate/hms_medicine_entry.conversion)/(1+(hms_medicine_entry.sgst+hms_medicine_entry.cgst+hms_medicine_entry.igst)/100))))-(hms_medicine_entry.discount*(hms_medicine_entry.purchase_rate/hms_medicine_entry.conversion))/100)
*/
}
    
    function medicine_profit_loss_report_list_20191202without_tax($get="")
    {
        $user_data = $this->session->userdata('auth_users');
        $purchase_where = '';
        $sale_where='';
        if(!empty($get['start_date']))
        {
            $from_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
            $to_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
        }
        
    

$sql1 = 'SELECT  `hms_medicine_entry`.`medicine_name` ,`hms_medicine_stock`.`created_date` as `stock_created_date`,`hms_medicine_stock`.`batch_no`, 

(select SUM(qtk.credit) from hms_medicine_stock as qtk where qtk.m_id=hms_medicine_entry.id AND `qtk`.`created_date` >= "'.$from_date.'" AND `qtk`.`created_date` <= "'.$to_date.'" AND qtk.branch_id='.$user_data['parent_id'].' AND `qtk`.is_deleted=0 AND qtk.type=3 AND qtk.batch_no=hms_medicine_stock.batch_no  GROUP BY `qtk`.`batch_no`) as sale_total_qty,

(select SUM(stk.total_amount)-(((SELECT sell.discount_percent from hms_medicine_sale as sell WHERE sell.id=stk.parent_id and stk.m_id=hms_medicine_entry.id AND stk.type=3 AND stk.branch_id='.$user_data['parent_id'].')*(stk.total_amount))/100)            from hms_medicine_stock as stk where stk.m_id=hms_medicine_entry.id AND `stk`.`created_date` >= "'.$from_date.'" AND `stk`.`created_date` <= "'.$to_date.'" AND stk.branch_id='.$user_data['parent_id'].' AND stk.type=3  AND `stk`.`is_deleted`=0  AND stk.batch_no=hms_medicine_stock.batch_no  GROUP BY `stk`.`batch_no`  ) as sale_total_price,


(select SUM(ptk.purchase_rate/ptk.conversion)-(((SELECT rett.discount_percent from hms_medicine_purchase as rett WHERE rett.id=ptk.parent_id and ptk.m_id=hms_medicine_entry.id AND ptk.type=1 AND ptk.branch_id='.$user_data['parent_id'].')*(ptk.total_amount))/100)    from hms_medicine_stock as ptk where ptk.m_id=hms_medicine_entry.id AND `ptk`.`created_date` >= "'.$from_date.'" AND `ptk`.`created_date` <= "'.$to_date.'" AND ptk.branch_id='.$user_data['parent_id'].' AND ptk.type=1 AND `ptk`.`is_deleted`=0  AND ptk.batch_no=hms_medicine_stock.batch_no GROUP BY `ptk`.`batch_no`  ) as purchase_total_price,


(select SUM(rtk.total_amount)-(((SELECT rett.discount_percent from hms_medicine_sale_return as rett WHERE rett.id=rtk.parent_id and rtk.m_id=hms_medicine_entry.id AND rtk.type=4 AND rtk.branch_id='.$user_data['parent_id'].')*(rtk.total_amount))/100)         from hms_medicine_stock as rtk where rtk.m_id=hms_medicine_entry.id AND `rtk`.`created_date` >= "'.$from_date.'" AND `rtk`.`created_date` <= "'.$to_date.'" AND rtk.branch_id='.$user_data['parent_id'].' AND rtk.type=4 AND `rtk`.`is_deleted`=0 AND rtk.batch_no=hms_medicine_stock.batch_no GROUP BY `rtk`.`batch_no`  ) as sale_return_total_price,


(select hms_medicine_entry.purchase_rate/hms_medicine_entry.conversion from hms_medicine_stock as otk where otk.m_id=hms_medicine_entry.id  AND otk.branch_id='.$user_data['parent_id'].' AND otk.type=6 AND `otk`.`is_deleted`=0 AND otk.batch_no=hms_medicine_stock.batch_no GROUP BY `otk`.`batch_no` ) as opening_stock_price




FROM `hms_medicine_entry` LEFT JOIN `hms_medicine_stock` ON `hms_medicine_stock`.`m_id` = `hms_medicine_entry`.`id` AND `hms_medicine_stock`.`is_deleted`=0


 WHERE `hms_medicine_entry`.`is_deleted` = "0" AND `hms_medicine_entry`.`branch_id` ='.$user_data['parent_id'].' AND `hms_medicine_stock`.`created_date` >= "'.$from_date.'" AND `hms_medicine_stock`.`created_date` <= "'.$to_date.'" AND `hms_medicine_stock`.`is_deleted`=0  AND `hms_medicine_stock`.`type`=3 GROUP BY `hms_medicine_stock`.`batch_no`, `hms_medicine_entry`.`id` ORDER BY `hms_medicine_stock`.`id` DESC, `hms_medicine_entry`.`medicine_name` DESC';

 $sql =  $this->db->query($sql1); 
//echo $this->db->last_query();die; 
//SUM(stk.total_amount/(1+(stk.sgst+stk.cgst+stk.igst)/100))
//SUM(ptk.purchase_rate/conversion (1+(ptk.sgst+ptk.cgst+ptk.igst)/100))
//SUM(rtk.total_amount/(1+(rtk.sgst+rtk.cgst+rtk.igst)/100)) 
return $sql->result_array();

         
}
    //profit loss update on 28 nov 2019
    function medicine_profit_loss_report_list20191128($get="")
    {
        $user_data = $this->session->userdata('auth_users');
        $purchase_where = '';
        $sale_where='';
        if(!empty($get['start_date']))
        {
            $from_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
            $to_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
        }
        
    

$sql1 = 'SELECT  `hms_medicine_entry`.`medicine_name` ,`hms_medicine_stock`.`created_date` as `stock_created_date`,`hms_medicine_stock`.`batch_no`, 

(select SUM(qtk.credit) from hms_medicine_stock as qtk where qtk.m_id=hms_medicine_entry.id AND `qtk`.`created_date` >= "'.$from_date.'" AND `qtk`.`created_date` <= "'.$to_date.'" AND qtk.branch_id='.$user_data['parent_id'].' AND `qtk`.is_deleted=0 AND qtk.type=3 AND qtk.batch_no=hms_medicine_stock.batch_no  GROUP BY `qtk`.`batch_no`) as sale_total_qty,

(select SUM(stk.total_amount) from hms_medicine_stock as stk where stk.m_id=hms_medicine_entry.id AND `stk`.`created_date` >= "'.$from_date.'" AND `stk`.`created_date` <= "'.$to_date.'" AND stk.branch_id='.$user_data['parent_id'].' AND stk.type=3  AND `stk`.`is_deleted`=0  AND stk.batch_no=hms_medicine_stock.batch_no  GROUP BY `stk`.`batch_no`  ) as sale_total_price,


(select SUM(ptk.purchase_rate/ptk.conversion)  from hms_medicine_stock as ptk where ptk.m_id=hms_medicine_entry.id AND `ptk`.`created_date` >= "'.$from_date.'" AND `ptk`.`created_date` <= "'.$to_date.'" AND ptk.branch_id='.$user_data['parent_id'].' AND ptk.type=1 AND `ptk`.`is_deleted`=0  AND ptk.batch_no=hms_medicine_stock.batch_no GROUP BY `ptk`.`batch_no`  ) as purchase_total_price,


(select SUM(rtk.total_amount) from hms_medicine_stock as rtk where rtk.m_id=hms_medicine_entry.id AND `rtk`.`created_date` >= "'.$from_date.'" AND `rtk`.`created_date` <= "'.$to_date.'" AND rtk.branch_id='.$user_data['parent_id'].' AND rtk.type=4 AND `rtk`.`is_deleted`=0 AND rtk.batch_no=hms_medicine_stock.batch_no GROUP BY `rtk`.`batch_no`  ) as sale_return_total_price,


(select hms_medicine_entry.purchase_rate/hms_medicine_entry.conversion from hms_medicine_stock as otk where otk.m_id=hms_medicine_entry.id  AND otk.branch_id='.$user_data['parent_id'].' AND otk.type=6 AND `otk`.`is_deleted`=0 AND otk.batch_no=hms_medicine_stock.batch_no GROUP BY `otk`.`batch_no` ) as opening_stock_price




FROM `hms_medicine_entry` LEFT JOIN `hms_medicine_stock` ON `hms_medicine_stock`.`m_id` = `hms_medicine_entry`.`id` AND `hms_medicine_stock`.`is_deleted`=0


 WHERE `hms_medicine_entry`.`is_deleted` = "0" AND `hms_medicine_entry`.`branch_id` ='.$user_data['parent_id'].' AND `hms_medicine_stock`.`created_date` >= "'.$from_date.'" AND `hms_medicine_stock`.`created_date` <= "'.$to_date.'" AND `hms_medicine_stock`.`is_deleted`=0  AND `hms_medicine_stock`.`type`=3 GROUP BY `hms_medicine_stock`.`batch_no`, `hms_medicine_entry`.`id` ORDER BY `hms_medicine_stock`.`id` DESC, `hms_medicine_entry`.`medicine_name` DESC';

 $sql =  $this->db->query($sql1); 
//echo $this->db->last_query();die; 
//SUM(stk.total_amount/(1+(stk.sgst+stk.cgst+stk.igst)/100))
//SUM(ptk.purchase_rate/conversion (1+(ptk.sgst+ptk.cgst+ptk.igst)/100))
//SUM(rtk.total_amount/(1+(rtk.sgst+rtk.cgst+rtk.igst)/100)) 
return $sql->result_array();

         
}
    
    function medicine_profit_loss_report_list20191107($get="")
    {
        $user_data = $this->session->userdata('auth_users');
        $purchase_where = '';
        $sale_where='';
        if(!empty($get['start_date']))
        {
            $from_date = date('Y-m-d',strtotime($get['start_date'])).' 00:00:00';
            $to_date = date('Y-m-d',strtotime($get['end_date'])).' 23:59:59';
            //$purchase_where = " AND P.purchase_date > ".$from_date." AND P.purchase_date < ".$to_date;
            //$sale_where = " AND S.sale_date > ".$from_date." AND S.sale_date < ".$to_date;
        }
        
    
        /*$this->db->select("S.sale_date as SaleDate, M.medicine_name as MedName,M.medicine_code as MedCode,D1.per_pic_price as SRate,((D1.per_pic_price)-((D1.discount)+(((D1.per_pic_price)*D1.sgst)/100)+(((D1.per_pic_price)*D1.cgst)/100)+(((D1.per_pic_price)*D1.igst)/100))) as MRP,D1.Qty,D.batch_no as BatchNo,(select SUM(mrp) from hms_medicine_stock as stk where stk.m_id=M.id AND stk.batch_no=D1.batch_no AND stk.branch_id='".$user_data['parent_id']."') as sale_price");

        $this->db->from("hms_medicine_entry as M",NULL, false);
          
        $this->db->join("hms_medicine_purchase_to_purchase as D","M.id = D.medicine_id",NULL, false);
          
        $this->db->join("hms_medicine_purchase as P","D.purchase_id = P.id AND P.purchase_date >='".$from_date."' AND P.purchase_date <='".$to_date."' AND P.branch_id=".$user_data['parent_id'],'left',NULL, false);
          
        $this->db->join("hms_medicine_sale_to_medicine as D1","M.id = D1.medicine_id",NULL, false);
          
        $this->db->join("hms_medicine_sale as S","D1.sales_id = S.id AND S.sale_date >='".$from_date."' AND S.sale_date <='".$to_date."' AND S.branch_id=".$user_data['parent_id'],'left',NULL, false);
        
        $this->db->where("D.batch_no=D1.batch_no",NULL, false);


        $this->db->where("M.branch_id",$user_data['parent_id'],NULL, false);
         
         $query = $this->db->get();
         echo $this->db->last_query(); exit;
         return $query->result();*/


         $sql1 = 'SELECT  `hms_medicine_entry`.`medicine_name` ,`hms_medicine_stock`.`created_date` as `stock_created_date`,`hms_medicine_stock`.`batch_no`, (select SUM(qtk.credit) from hms_medicine_stock as qtk where qtk.m_id=hms_medicine_entry.id AND `qtk`.`created_date` >= "'.$from_date.'" AND `qtk`.`created_date` <= "'.$to_date.'" AND qtk.branch_id='.$user_data['parent_id'].' AND qtk.type=3 AND qtk.batch_no=hms_medicine_stock.batch_no  GROUP BY `qtk`.`batch_no`  ) as sale_total_qty,

(select SUM(stk.total_amount/(1+(stk.sgst+stk.cgst+stk.igst)/100)) from hms_medicine_stock as stk where stk.m_id=hms_medicine_entry.id AND `stk`.`created_date` >= "'.$from_date.'" AND `stk`.`created_date` <= "'.$to_date.'" AND stk.branch_id='.$user_data['parent_id'].' AND stk.type=3 AND stk.batch_no=hms_medicine_stock.batch_no  GROUP BY `stk`.`batch_no`  ) as sale_total_price,


(select SUM(ptk.total_amount/(1+(ptk.sgst+ptk.cgst+ptk.igst)/100)) from hms_medicine_stock as ptk where ptk.m_id=hms_medicine_entry.id AND `ptk`.`created_date` >= "'.$from_date.'" AND `ptk`.`created_date` <= "'.$to_date.'" AND ptk.branch_id='.$user_data['parent_id'].' AND ptk.type=1  AND ptk.batch_no=hms_medicine_stock.batch_no GROUP BY `ptk`.`batch_no`  ) as purchase_total_price 


FROM `hms_medicine_entry` LEFT JOIN `hms_medicine_stock` ON `hms_medicine_stock`.`m_id` = `hms_medicine_entry`.`id` AND `hms_medicine_stock`.`is_deleted`=0 

 WHERE `hms_medicine_entry`.`is_deleted` = "0" AND `hms_medicine_entry`.`branch_id` ='.$user_data['parent_id'].' AND `hms_medicine_stock`.`created_date` >= "'.$from_date.'" AND `hms_medicine_stock`.`created_date` <= "'.$to_date.'" AND `hms_medicine_stock`.`type` IN(1,3) GROUP BY `hms_medicine_stock`.`batch_no`, `hms_medicine_entry`.`id` ORDER BY `hms_medicine_stock`.`id` DESC, `hms_medicine_entry`.`medicine_name` DESC';

 $sql =  $this->db->query($sql1); 
//echo $this->db->last_query();die; 
return $sql->result_array();

         
}


}
?>