<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        // カテゴリー一覧を取得
        $categories = Category::all();

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

        // タグ一覧を取得
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    // お問い合わせ詳細表示
    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    // お問い合わせ削除
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect('/admin');
    }

    // エクスポート機能

    public function export(ExportContactRequest $request): StreamedResponse
    {
        // indexと同じクエリ条件
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

        // 全件を新着順で取得
        $query->orderBy('created_at', 'desc');

        // CSV出力設定
        $filename = 'contact.csv';
        $headers = [
            'Content-Type' => 'text/csv;charset=UTF-8',
            'Content-Disposition' => "attachment;filename=\"{$filename}\"",
        ];

        // $queryデータを、ブラウザへ直接流し込み、逐次ダウンロードさせる
        return response()->stream(function () use ($query) {
            $file = fopen('php://output', 'w');

            // BOM付CSV出力
            fwrite($file, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv($file, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時',
            ]);

            // 200件ずつ分割処理
            $query->chunk(200, function ($contacts) use ($file) {
                foreach ($contacts as $contact) {
                    $fullName = trim(($contact->last_name ?? '').'　'.($contact->first_name ?? ''));

                    fputcsv($file, [
                        $contact->id,
                        $fullName,
                        $contact->gender_label,
                        $contact->email,
                        $contact->tel,
                        $contact->address,
                        $contact->building,
                        $contact->category?->content ?? '',
                        $contact->detail,
                        $contact->created_at ? $contact->created_at->format('Y-m-d H:i:s') : '',
                    ]);
                }
            });
            fclose($file);
        }, 200, $headers);
    }
}
