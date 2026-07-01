<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatusAlatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'device_id' => 'required|string|max:255',
            'nama_perangkat' => 'required|string|max:255',
            'berat_pakan' => 'required|numeric|min:0',
            'status_motor' => 'required|string|in:aktif,mati',
            'status_sensor' => 'required|string|in:normal,rusak',
            'mode_operasi' => 'required|string|in:otomatis,manual',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'device_id.required' => 'Device ID wajib diisi.',
            'nama_perangkat.required' => 'Nama perangkat wajib diisi.',
            'berat_pakan.required' => 'Berat pakan wajib diisi.',
            'berat_pakan.numeric' => 'Berat pakan harus berupa angka.',
            'status_motor.required' => 'Status motor wajib diisi.',
            'status_motor.in' => 'Status motor harus bernilai aktif atau mati.',
            'status_sensor.required' => 'Status sensor wajib diisi.',
            'status_sensor.in' => 'Status sensor harus bernilai normal atau rusak.',
            'mode_operasi.required' => 'Mode operasi wajib diisi.',
            'mode_operasi.in' => 'Mode operasi harus bernilai otomatis atau manual.',
        ];
    }
}
