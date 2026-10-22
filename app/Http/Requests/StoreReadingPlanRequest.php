<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReadingPlanRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                'exists:books,id',
            ],
            'target_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('book_id')) {
                return;
            }

            $user = $this->user();

            if ($user === null) {
                return;
            }

            $duplicateExists = ReadingPlan::query()
                ->where('user_id', $user->id)
                ->where('book_id', $this->integer('book_id'))
                ->where('status', '!=', ReadingPlanStatus::Completed->value)
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add(
                    'book_id',
                    'この書籍には既に読書計画が登録されています。'
                );
            }
        });
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.integer' => '選択された書籍が正しくありません。',
            'book_id.exists' => '選択された書籍が正しくありません。',
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は正しい日付を入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を入力してください。',
        ];
    }
}
