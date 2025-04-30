<?php

namespace App\Http\Controllers;

use App\Enums\Frontend\CommentStatus;
use App\Models\Frontend\Blog\BlogComment;
use App\Models\Frontend\Blog\BlogPost;
use function App\Helpers\sanitize_input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlogCommentController extends Controller
{
    use AuthorizesRequests;

    public function index(BlogPost $post, Request $request)
    {
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $user = $request->user();

        return response()->json([
            'comments' => $post->comments()
                ->whereNull('parent_id')
                ->where('status', CommentStatus::APPROVED)
                ->orderBy($sortField, $sortDirection)
                ->get()
        ]);
    }

    public function store(Request $request, BlogPost $post)
    {
        $user = $request->user();

        if (!Auth::check()) {
            return response()->json([
                'message' => 'You must be logged in to submit a comment.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:blog_comments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['content'] = sanitize_input($validated['content']);
        $validated['author_name'] = sanitize_input($validated['author_name'] ?? $user->name);
        $validated['email'] = sanitize_input($validated['email'] ?? $user->email);

        $comment = new BlogComment($validated);
        $comment->post_id = $post->id;
        $comment->status = CommentStatus::PENDING;
        $comment->save();

        if (!$comment->parent_id) {
            $comment->load('replies');
        }

        return response()->json([
            'message' => 'Comment submitted successfully and is awaiting moderation.',
            'comment' => $comment
        ], 201);
    }

    public function approve(BlogComment $comment)
    {

        // TODO: Add send email to user that comment was approved
        $this->authorize('approve', $comment);

        $comment->update(['status' => CommentStatus::APPROVED]);

        return response()->json([
            'message' => 'Comment approved successfully',
            'comment' => $comment
        ]);
    }

    public function destroy(BlogComment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully'
        ]);
    }
}
