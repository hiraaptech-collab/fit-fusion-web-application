<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareViews extends Command
{
    protected $signature='fitfusion:prepare-views';
    protected $description='Precompile Blade templates using the paths used by actual web requests.';

    public function handle(): int
    {
        $this->call('view:cache');
        $finder=app('view')->getFinder();$compiler=app('blade.compiler');$count=0;
        $directories=[''=>[resource_path('views')]]+$finder->getHints();
        foreach($directories as $namespace=>$paths){
            foreach($paths as $path){
                if(!is_dir($path))continue;
                foreach(File::allFiles($path) as $file){
                    if(!str_ends_with($file->getFilename(),'.blade.php'))continue;
                    $name=str_replace(['\\','/'],'.',substr($file->getRelativePathname(),0,-10));
                    if($namespace!=='')$name=$namespace.'::'.$name;
                    // Windows realpath and web view-finder paths can hash differently.
                    $compiler->compile($finder->find($name));$count++;
                }
            }
        }
        $this->info('Prepared '.$count.' runtime view paths.');
        return self::SUCCESS;
    }
}
