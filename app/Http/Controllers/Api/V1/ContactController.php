<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexContactRequest;
use App\Http\Requests\Api\StoreContactRequest;
use App\Http\Requests\Api\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    // お問い合わせ一覧取得

    public function index(IndexContactRequest $request): AnonymousResourceCollection
    {
        // お問い合わせの検索クエリを作成
        $query = Contact::with(['category', 'tags']);

        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%");
            });
        }

        // 性別検索
        if (in_array($request->input('gender'), ['1', '2', '3'], false)) {
            $query->where('gender', $request->input('gender'));
        }

        // お問い合わせの種類検索
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // 日付検索
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // 1ページあたり7件取得
        $contacts = $query->orderBy('created_at', 'desc')->paginate(7);

        return ContactResource::collection($contacts);
    }

    // お問い合わせ詳細表示
    public function show(Contact $contact): ContactResource
    {
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    // お問い合わせ作成
    public function store(StoreContactRequest $request): ContactResource
    {
        $validated = $request->validated();

        $contact = Contact::create($validated);

        if (! empty($validated['tag_ids'])) {
            $contact->tags()->sync($validated['tag_ids']);
        }

        return new ContactResource($contact->load(['category', 'tags']));
    }

    // お問い合わせ更新
    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $validated = $request->validated();

        $contact->update($validated);

        $contact->tags()->sync($validated['tag_ids'] ?? []);

        return new ContactResource($contact->load(['category', 'tags']));
    }

    // お問い合わせ削除
    public function destroy(Contact $contact): Response
    {
        $contact->delete();

        return response()->noContent();
    }
}
