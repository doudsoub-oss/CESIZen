<?php

namespace App\Http\Requests\Admin\Menus;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Menu::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'in:main,footer,sidebar'],
        ];
    }
}
