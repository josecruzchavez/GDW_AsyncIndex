<?php
declare(strict_types=1);

namespace GDW\AsyncIndex\Helper;

final class GdwModuleMeta
{
    /** @return array{desc:string, category:string, config_path:string, config_anchor:string, repo_url:string, docs_url:string, pro_module:string, pro_url:string} */
    public static function getMeta(): array
    {
        return [
            'desc' => 'Edición Free: cola, cron, reindexación, pendientes, invalidación, reindexación completa, estado y trabajos recientes.',
            'category' => 'Freemium',
            'config_path' => 'adminhtml/system_config/edit/section/gdwdeveloper',
            'config_anchor' => '#gdwdeveloper_asyncindex-link',
            'repo_url' => 'https://github.com/josecruzchavez/GDW_AsyncIndex',
            'docs_url' => 'https://docs.gdw.mx/modulos/gdw_asyncindex',
            'pro_module' => 'GDW_AsyncIndexPro',
            'pro_url' => 'https://gdw.mx/modulos/gdw-asyncindexpro/',
        ];
    }
}
