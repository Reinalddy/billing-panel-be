<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstalledPlugin;
use App\Models\Plugin;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index(Request $request)
    {
        $plugins = Plugin::query()
            ->when(
                $request->search,
                fn($q) =>
                $q->where('name', 'ILIKE', "%{$request->search}%")
            )
            ->paginate(6);

        $installed = InstalledPlugin::where('user_id', $request->user()->id)
            ->pluck('plugin_id')
            ->toArray();

        return response()->json([
            'code' => 200,
            'data' => [
                'plugins' => $plugins,
                'installed' => $installed,
            ],
        ]);
    }

    public function install(Request $request, $id)
    {
        InstalledPlugin::firstOrCreate([
            'user_id' => $request->user()->id,
            'plugin_id' => $id,
        ]);

        return response()->json([
            'code' => 200,
            'message' => 'Plugin installed',
        ]);
    }
}
