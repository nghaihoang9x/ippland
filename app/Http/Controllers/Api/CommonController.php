<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
class CommonController extends Controller
{
    public function viewCount()
    {
        $model = ucfirst(request()->get('model'));
        $id = request()->get('id');
        if (!$id || !$model) return;

        $model = "\App\Models\\$model";
        $model::where('_id', $id)->increment('view_count');

    }
}