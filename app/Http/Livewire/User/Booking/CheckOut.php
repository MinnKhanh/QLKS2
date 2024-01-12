<?php

namespace App\Http\Livewire\User\Booking;

use App\Enums\StatusBookingEnum;
use App\Enums\StatusRoomEnum;
use App\Enums\TaxEnum;
use App\Enums\TypeBooking;
use App\Enums\TypePriceEnum;
use App\Enums\TypeTimeEnum;
use App\Jobs\SendCheckOut;
use App\Jobs\SendPayment;
use App\Mail\SendMailCheckOut;
use App\Models\Bill;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomTypeDetail;
use App\Models\TimeLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Throwable;

class CheckOut extends Component
{
    public $roomType;
    public $roomTypeDetail;
    public $fromDateTime;
    public $toDateTime;
    public $numberOfRoom = 1;
    public $dates = [];
    public $name;
    public $phone;
    public $cmtnd;
    public $email;
    public $rentalTime = 0;
    public $customer;
    public $customerAccount;
    public $adult;
    public $children;
    public $deposit;
    public $note;
    public $countroom;
    protected $listeners = ['setfromDate', 'settoDate'];

    public function mount()
    {
        $this->numberOfRoom = 1;
        if (auth()->check()) {
            $customer = Customer::where('account_id', auth()->user()->id)->first();
            if ($customer) {
                $this->customerAccount = $customer;
                $this->name = $customer->name;
                $this->phone = $customer->phone;
                $this->cmtnd = $customer->cmtnd;
                $this->email = $customer->email;
            }
        }
        $this->roomTypeDetail = RoomTypeDetail::with(['Img', 'Room', 'RoomCapacity', 'Service', 'TypeRoom', 'Price' => function ($q) {
            $q->where('type_price', TypePriceEnum::DAY);
        }, 'Convenient'])->where('id', $this->roomType)->first()->toArray();
        $this->changeFromAndToDateTime();
        // dd($this->roomTypeDetail);
        // $bookings = Booking::where('room_id', $this->idRoom)->whereIn('status', [StatusBookingEnum::PENDING, StatusBookingEnum::ACTIVE])->orderBy('checkin_date', 'ASC')->get()->toArray();
        // foreach ($bookings as $item) {
        //     $this->dates[] = [$item['checkin_date'], $item['checkout_date']];
        // }
        // $this->room = Room::with(['Img', 'Type', 'Floor', 'Service', 'Convenient'])->where('id', $this->idRoom)->first()->toArray();
        // DB::enableQueryLog();
        $listRoom = Room::where('type_room', $this->roomType)->where('status', StatusRoomEnum::EMPTY)->whereDoesntHave('Booking', function ($q) {
            $q->whereIn('status', [StatusBookingEnum::PENDING]);
            $q->where(function ($query) {
                $query->where(function ($qu) {
                    $qu->whereDate('checkin_date', '>=', date('Y-m-d', strtotime($this->fromDateTime)));
                    $qu->whereDate('checkout_date', '<=', date('Y-m-d', strtotime($this->toDateTime)));
                });
                $query->orWhere(function ($qu) {
                    $qu->whereDate('checkin_date', '<=', date('Y-m-d', strtotime($this->fromDateTime)));
                    $qu->whereDate('checkout_date', '>=', date('Y-m-d', strtotime($this->fromDateTime)));
                });
                $query->orWhere(function ($qu) {
                    $qu->whereDate('checkin_date', '<=', date('Y-m-d', strtotime($this->toDateTime)));
                    $qu->whereDate('checkout_date', '>=', date('Y-m-d', strtotime($this->toDateTime)));
                });
            });
        });
        // dd(DB::getQueryLog());
        // ->whereHas('Capacity', function ($q) {
        //     if ($this->adult)
        //         $q->where('number_of_adults', '>=', $this->adult);
        //     if ($this->children)
        //         $q->where('number_of_children', '>=', $this->children);
        // });
        $this->countroom = $listRoom->count();
        // dd($listRoom->get()->toArray());
    }
    public function changeFromAndToDateTime()
    {
        if ($this->fromDateTime && $this->toDateTime) {
            $this->rentalTime = Carbon::parse($this->fromDateTime)->diffInDays(Carbon::parse($this->toDateTime));
        }
    }
    public function render()
    {
        $this->updateUI();
        return view('livewire.user.booking.check-out');
    }
    public function updateUI()
    {
        $this->dispatchBrowserEvent('setSelect2');
        $this->dispatchBrowserEvent('setDatePicker');
    }
    public function setfromDate($time)
    {
        $this->fromDateTime = date('Y-m-d', strtotime($time['fromDateTime']));
        $this->changeFromAndToDateTime();
    }
    public function settoDate($time)
    {
        $this->toDateTime = date('Y-m-d', strtotime($time['toDateTime']));
        $this->changeFromAndToDateTime();
    }
    public function checkOut()
    {
        // dd($this->numberOfRoom);
        $this->validate([
            'email' => 'required|email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'cmtnd' => 'required',
            'name' => 'required',
            'fromDateTime' => 'required',
            'rentalTime' => 'required',
            'toDateTime' => 'required',
            'toDateTime' => 'required',
            'rentalTime' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $customer = Customer::where('email', $this->email)->where('phone', $this->phone)->where('cmtnd', $this->cmtnd)->first();
            if (auth()->check()) {
                if ($this->email != $this->customerAccount->email || $this->phone != $this->customerAccount->phone || $this->cmtnd != $this->customerAccount->cmtnd) {
                    Customer::where('id', $this->customerAccount->id)->update(['email' => $this->email, 'cmtnd' => $this->cmtnd, 'phone' => $this->phone]);
                    $customer = Customer::where('id', $this->customerAccount->id)->first();
                }
            } else {
                if (!$customer) {
                    $customer = Customer::create([
                        'code' => 'KH' . Customer::count(),
                        'email' => $this->email,
                        'phone' => $this->phone,
                        'cmtnd' => $this->cmtnd,
                        'name' => $this->name,
                    ]);
                } else {
                    $customer->name = $this->name;
                    $customer->save();
                }
            }
            $listRoom = Room::with(['Type', 'Floor', 'Capacity'])->where('type_room', $this->roomType)->whereDoesntHave('Booking', function ($q) {
                $q->whereIn('status', [StatusBookingEnum::PENDING]);
                $q->where(function ($query) {
                    $query->where(function ($qu) {
                        $qu->whereDate('checkin_date', '>=', date('Y-m-d', strtotime($this->fromDateTime)));
                        $qu->whereDate('checkout_date', '<=', date('Y-m-d', strtotime($this->toDateTime)));
                    });
                    $query->orWhere(function ($qu) {
                        $qu->whereDate('checkin_date', '<=', date('Y-m-d', strtotime($this->fromDateTime)));
                        $qu->whereDate('checkout_date', '>=', date('Y-m-d', strtotime($this->fromDateTime)));
                    });
                    $query->orWhere(function ($qu) {
                        $qu->whereDate('checkin_date', '<=', date('Y-m-d', strtotime($this->toDateTime)));
                        $qu->whereDate('checkout_date', '>=', date('Y-m-d', strtotime($this->toDateTime)));
                    });
                });
            })->whereHas('Capacity', function ($q) {
                if ($this->adult)
                    $q->where('number_of_adults', '>=', $this->adult);
                if ($this->children)
                    $q->where('number_of_children', '>=', $this->children);
            })->orderBy('name', 'ASC')->limit($this->numberOfRoom)->get();
            $lisRoomShow = $listRoom;
            $listRoom = $listRoom->toArray();
            $this->deposit = 0.2 * $this->roomTypeDetail['price'][0]['price'] * $this->rentalTime * $this->numberOfRoom * (1 + TaxEnum::TAX / 100);
            $dataInsert = [];
            $roomId = [];
            $ids = [];
            foreach ($listRoom as $item) {
                $book = new Booking();
                $book->customer_id = $customer->id;
                $book->room_id = $item['id'];
                $book->note = $this->note;
                $book->type = TypeBooking::RESERVE;
                $book->status = StatusBookingEnum::PENDING;
                $book->checkin_date = $this->fromDateTime;
                $book->checkout_date = $this->toDateTime;
                $book->rental_time =  $this->rentalTime;
                $timeLine = TimeLine::where('type_time', TypeTimeEnum::DAY)->first()->toArray();
                $book->hour_in = $timeLine['start_hour'];
                $book->type_time = TypeTimeEnum::DAY;
                $book->number_of_adults = $this->adult;
                $book->number_of_children = $this->children;
                $book->deposit = $this->deposit ?? 0;
                $book->save();
                $ids[] = $book->id;
                $roomId[] = $item['id'];
                // $data = [];
                // $data['customer_id'] = $customer->id;
                // $data['room_id'] = $item['id'];
                // $data['note'] = $this->note;
                // $data['type'] = TypeBooking::RESERVE;
                // $data['status'] = StatusBookingEnum::PENDING;
                // $data['checkin_date'] = $this->fromDateTime;
                // $data['checkout_date'] = $this->toDateTime;
                // $data['rental_time'] =  $this->rentalTime;
                // $timeLine = TimeLine::where('type_time', TypeTimeEnum::DAY)->first()->toArray();
                // $data['hour_in'] = $timeLine['start_hour'];
                // $data['type_time'] = TypeTimeEnum::DAY;
                // $data['number_of_adults'] = $this->adult;
                // $data['number_of_children'] = $this->children;
                // $data['deposit'] = $this->deposit ?? 0;
                // $dataInsert[] = $data;
            }
            // Booking::insert($dataInsert);
            DB::commit();
            // dd($lisRoomShow);
            Mail::to($this->email)->send(new SendMailCheckOut($customer, $lisRoomShow));

            $this->vnPay($this->deposit, $customer, $ids, $roomId);
            return;
            return redirect()->route('info_booking', ['idcustomer' => $customer->id, 'idbooking' => $ids]);
            // SendPayment::dispatch($this->email,$customer, $listRoom);
        } catch (Throwable $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('show-toast', ['type' => 'error', 'message' => "Tạo thất bại"]);
            return;
        }
    }

    public function vnPay($money,$customer, $ids, $roomId) {

        $bill = Bill::create([
            'creator_id' => $customer->id,
            'phone' => $this->phone,
            'email' => $this->email,
            'note' => $this->note,
            'name' => $this->name,
            'status' => 1,
            'total_price' => $money,
            'type'=> 1
        ]);

        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = route('info_booking', ['idcustomer' => $customer->id, 'idbooking' => $ids]);
        // dd($vnp_Returnurl);
        $vnp_TmnCode = "K1QSQANU"; //Mã website tại VNPAY
        $vnp_HashSecret = "LDGDKDKDKKHXXHGENZSXNNFIAHOSZGPD"; //Chuỗi bí mật

        $vnp_TxnRef = $bill->id; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $vnp_OrderInfo = 'Thanh toán đơn hàng';
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $money * 100;
        $vnp_Locale = 'vn';
        $vnp_BankCode = 'NCB';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        //Add Params of 2.0.1 Version
        // $vnp_ExpireDate = $_POST['txtexpire'];
        //Billing
        // $vnp_Bill_Mobile = $_POST['txt_billing_mobile'];
        // $vnp_Bill_Email = $_POST['txt_billing_email'];
        // $fullName = trim($_POST['txt_billing_fullname']);
        // if (isset($fullName) && trim($fullName) != '') {
        //     $name = explode(' ', $fullName);
        //     $vnp_Bill_FirstName = array_shift($name);
        //     $vnp_Bill_LastName = array_pop($name);
        // }
        // $vnp_Bill_Address = $_POST['txt_inv_addr1'];
        // $vnp_Bill_City = $_POST['txt_bill_city'];
        // $vnp_Bill_Country = $_POST['txt_bill_country'];
        // $vnp_Bill_State = $_POST['txt_bill_state'];
        // // Invoice
        // $vnp_Inv_Phone = $_POST['txt_inv_mobile'];
        // $vnp_Inv_Email = $_POST['txt_inv_email'];
        // $vnp_Inv_Customer = $_POST['txt_inv_customer'];
        // $vnp_Inv_Address = $_POST['txt_inv_addr1'];
        // $vnp_Inv_Company = $_POST['txt_inv_company'];
        // $vnp_Inv_Taxcode = $_POST['txt_inv_taxcode'];
        // $vnp_Inv_Type = $_POST['cbo_inv_type'];
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            // "vnp_ExpireDate" => $vnp_ExpireDate,
            // "vnp_Bill_Mobile" => $vnp_Bill_Mobile,
            // "vnp_Bill_Email" => $vnp_Bill_Email,
            // "vnp_Bill_FirstName" => $vnp_Bill_FirstName,
            // "vnp_Bill_LastName" => $vnp_Bill_LastName,
            // "vnp_Bill_Address" => $vnp_Bill_Address,
            // "vnp_Bill_City" => $vnp_Bill_City,
            // "vnp_Bill_Country" => $vnp_Bill_Country,
            // "vnp_Inv_Phone" => $vnp_Inv_Phone,
            // "vnp_Inv_Email" => $vnp_Inv_Email,
            // "vnp_Inv_Customer" => $vnp_Inv_Customer,
            // "vnp_Inv_Address" => $vnp_Inv_Address,
            // "vnp_Inv_Company" => $vnp_Inv_Company,
            // "vnp_Inv_Taxcode" => $vnp_Inv_Taxcode,
            // "vnp_Inv_Type" => $vnp_Inv_Type
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
            $inputData['vnp_Bill_State'] = $vnp_Bill_State;
        }

        //var_dump($inputData);
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        $returnData = array(
            'code' => '00', 'message' => 'success', 'data' => $vnp_Url
        );
        if (isset($vnp_Url)) {
            // header('Location: ' . $vnp_Url );
            return redirect()->to($vnp_Url);
            die();
        } else {
            $this->dispatchBrowserEvent('show-toast', ['type' => 'error', 'message' => "Tạo thất bại"]);
            return;
        }
            // vui lòng tham khảo thêm tại code demo
    }
}
