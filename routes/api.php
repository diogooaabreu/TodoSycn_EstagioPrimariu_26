use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/tasks', function (Request $request) {
    return Todo::where('user_id', $request->user_id)->get();
});

Route::post('/tasks', function (Request $request) {
    return Todo::create($request->all());
});
