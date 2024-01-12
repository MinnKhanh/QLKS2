<?php

namespace App\Http\Livewire\User\Booking;

use App\Enums\StatusRoomEnum;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use Livewire\Component;

class InfoBooking extends Component
{
    public $idCustomer;
    public $idBooking;
    public $customer;
    public $listBooking;
    public $isSucces=true;
    public $success;

    public function mount(){
        // dd($this->success);
        if(!$this->success){
            dd(1);
            Booking::whereIn('id',$this->idBooking)->delete();
             $this->isSucces = false;
        }
        $this->customer = Customer::where('id',$this->idCustomer)->first()->toArray();
        $this->listBooking=Booking::with(['Room'])->whereIn('id',$this->idBooking)->get()->toArray();
    }
    public function render()
    {
        if(!$this->isSucces){
            redirect()->route('room.index');
        }
        return view('livewire.user.booking.info-booking');
    }
}
