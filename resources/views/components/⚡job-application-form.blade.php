<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\JobApplication;

class JobApplicationForm extends Component
{
    public $jobId;
    public $full_name;
    public $email;
    public $phone_number;
    public $birthplace;
    public $current_address;
    public $gender;
    public $birthdate;

    public function mount($jobId)
    {
        $this->jobId = $jobId;
    }

    public function submit()
    {
        $data = $this->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_number' => 'required|string|max:20',
            'birthplace' => 'required|string|max:255',
            'current_address' => 'required|string|max:500',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
        ]);

        JobApplication::updateOrCreate(
            ['job_id' => $this->jobId, 'user_id' => auth()->id() ?? null],
            $data
        );

        return redirect('/thank-you'); // or next step
    }

    public function render()
    {
        return view('livewire.job-application-form');
    }
}