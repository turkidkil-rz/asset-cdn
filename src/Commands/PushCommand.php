<?php

namespace Arubacao\AssetCdn\Commands;

use Illuminate\Http\File;
use Arubacao\AssetCdn\Finder;
use Illuminate\Config\Repository;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Filesystem\FilesystemManager;

class PushCommand extends BaseCommand
{
    protected $signature = 'asset-cdn:push';

    protected $description = 'Pushes assets to CDN';

    private Repository $config;

    private FilesystemManager $fileSystemManager;

    public function __construct(Repository $config, FilesystemManager $fileSystemManager)
    {
        $this->config = $config;
        $this->fileSystemManager = $fileSystemManager;

        parent::__construct();
    }

    public function handle(Finder $finder)
    {
        $files = collect($finder->getFiles());

        if (! $this->upload($files->first())) {
            $this->error('Failed to upload the files to the CDN.');

            return 1;
        }

        $this->info("\nUploading files to CDN...\n");

        $this->withProgressBar($files, function (SplFileInfo $file) {
            $self = $this;

            async(fn (): bool => $self->upload($file));
        });

        $this->newLine(2);
    }

    private function upload(SplFileInfo $file)
    {
        return $this->fileSystemManager
                ->disk($this->config->get('asset-cdn.filesystem.disk'))
                ->putFileAs(
                    $file->getRelativePath(),
                    new File($file->getPathname()),
                    $file->getFilename(),
                    $this->config->get('asset-cdn.filesystem.options')
                );
    }
}
