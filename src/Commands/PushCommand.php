<?php

namespace Arubacao\AssetCdn\Commands;

use Illuminate\Http\File;
use Arubacao\AssetCdn\Finder;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\FilesystemManager;

class PushCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asset-cdn:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pushes assets to CDN';

    /**
     * Execute the console command.
     *
     * @param Finder $finder
     * @param FilesystemManager $filesystemManager
     * @param Repository $config
     *
     * @return void
     */
    public function handle(Finder $finder, FilesystemManager $filesystemManager, Repository $config)
    {
        $this->info("\nUploading files to CDN...\n");

        $this->withProgressBar($finder->getFiles(), function ($file) use ($filesystemManager, $config) {
            async(function () use ($file, $filesystemManager, $config): bool {
                return $filesystemManager
                    ->disk($config->get('asset-cdn.filesystem.disk'))
                    ->putFileAs(
                        $file->getRelativePath(),
                        new File($file->getPathname()),
                        $file->getFilename(),
                        $config->get('asset-cdn.filesystem.options')
                    );
            });
        });

        $this->newLine(2);
    }
}
