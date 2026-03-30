use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rota para o telemóvel listar as tarefas do MySQL
Route::get('/tasks', function () {
    return Task::all(); 
});

// Rota para o telemóvel criar uma tarefa no MySQL
Route::post('/tasks', function (Request $request) {
    return Task::create($request->all());
});
