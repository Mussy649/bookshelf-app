<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'distinct', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '登録者IDは必須です。',
            'user_id.integer' => '登録者IDは整数で入力してください。',
            'user_id.exists' => '指定された登録者は存在しません。',

            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',

            'author.required' => '著者名は必須です。',
            'author.max' => '著者名は255文字以内で入力してください。',

            'isbn.required' => 'ISBNは必須です。',
            'isbn.digits' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => 'このISBNは既に登録されています。',

            'published_date.required' => '出版日は必須です。',
            'published_date.date' => '有効な日付を入力してください。',

            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は5000文字以内で入力してください。',

            'image_url.url' => '正しいURL形式で入力してください。',
            'image_url.max' => '画像URLは2048文字以内で入力してください。',

            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.array' => 'ジャンルを1つ以上選択してください。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',

            'genres.*.integer' => '選択されたジャンルが正しくありません。',
            'genres.*.distinct' => '同じジャンルを重複して選択することはできません。',
            'genres.*.exists' => '選択されたジャンルが正しくありません。',
        ];
    }
}
