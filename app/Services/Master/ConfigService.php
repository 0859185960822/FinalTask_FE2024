<?php

namespace App\Services\Master;

use App\Models\Config;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConfigService
{
    /**
     * getConfig
     *
     * @param  null
     * @return mixed
     */
    public static function getConfigArrayAll()
    {
        $query = Config::query();
        $query->select('slug', 'value');
        $data = $query->get();
        return $data;
    }

    /**
     * getConfig
     *
     * @param  String $slug | not mandatory
     * @return mixed
     */
    public static function getConfig($slug = "")
    {
        $json = [];
        $query = Config::query();
        $query->select('slug', 'value');
        if ($slug != "") {
            $query->where("slug", $slug);
            $data = $query->first();
            return $data;
        }
        $data = $query->get();

        foreach ($data as $kf) {
            $json_value = array($kf->slug => $kf->value);
            $json = array_merge($json, $json_value);
        }

        return $json;
    }

    /**
     * store
     * update config
     *
     * @param  Array $payload
     * @return Array
     */
    public static function update($payload): array
    {
        DB::beginTransaction();
        try {
            // $string_data = $payload['logoFullDark'];
            foreach (json_decode($payload['valueConfigurasi']) as $value) {
                if (($value->slug == 'LOGO_FULL_LIGHT') && ($payload['statusLogoFullLight'] == true)) {
                    $val = self::updateFoto($value->slug, $payload['logoFullLight']);
                    Config::where("slug", "LOGO_FULL_LIGHT")->update([
                        'value' => $val
                    ]);
                } else if (($value->slug == 'LOGO_FULL_DARK') && ($payload['statusLogoFullDark'] == true)) {
                    $val = self::updateFoto($value->slug, $payload['logoFullDark']);
                    Config::where("slug", "LOGO_FULL_DARK")->update([
                        'value' => $val
                    ]);
                } else if ($value->slug == 'LOGIN_BACKGROUND' && $payload['statusLoginBackground'] == true) {
                    $val = self::updateFoto($value->slug, $payload['loginBackground']);
                    Config::where("slug", 'LOGIN_BACKGROUND')->update([
                        'value' => $val
                    ]);
                } else {
                    Config::where("slug", $value->slug)->update([
                        'value' => $value->value
                    ]);
                }
            }

            DB::commit();
            return [
                'status' => true,
                'data' => "success",
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'status' => false,
                'errors' => $th->getMessage(),
            ];
        }
    }

    private static function updateFoto($slug, $value): string
    {
        $url_foto = $value;
        $path_file = $value;
        $explode_file = explode("/", $path_file);
        $name_file = $explode_file[3] ?? '';

        $explode_name_file = explode(".", $name_file);
        $ext_file = $explode_name_file[1] ?? '';
        if (Storage::disk('public')->exists('temporary_file/' . $name_file) && $name_file) {
            $name_file_new = "$slug-" . Carbon::now()->format('Ymd_H_i_s') . "." . $ext_file;
            $folder = $slug == 'LOGIN_BACKGROUND' ? 'login_background' : 'logo_icon_apps';
            $moved = 'public/' . $folder . '/' . $name_file_new;

            Storage::move('public/temporary_file/' . $name_file, $moved);
            Storage::delete('temporary_file/' . $name_file);

            $url_foto = Storage::url($moved);
        }

        return $url_foto;
    }

    public static function uploadFile($request)
    {
        $date_time = Carbon::now()->format('Ymd_H_i_s');
        $file_data = $request->file('UPLOADED_FILE_REFERENSI');
        $file_ext = $file_data->extension();
        $file_upload = $file_data->storeAs('public/temporary_file', 'temporary_file-' . $date_time . "." . $file_ext);
        $url_file = Storage::url($file_upload);

        return $url_file;
    }
}
