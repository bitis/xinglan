<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * 评论列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $comments = Comment::where('order_id', $request->integer('order_id'))
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($comments);
    }

    public function form(Request $request): JsonResponse
    {
        $user = $request->user();

        $comment = Comment::findOr($request->input('id'), fn() => new Comment);

        if (!empty($comment->user_id) and $comment->user_id !== $user->id) return fail('不可修改他人留言');

        $comment->fill($request->only([
            'order_id',
            'content'
        ]));

        $comment->user_id = $user->id;
        $comment->company_id = $user->company_id;

        $comment->save();

        return success();
    }

    public function delete(Request $request): JsonResponse
    {
        $user = $request->user();

        $comment = Comment::findOr($request->input('id'), new Comment);

        if (!empty($comment->user_id) and $comment->user_id !== $user->id) return fail('不可修改他人留言');

        $comment->delete();

        return success();
    }
}
