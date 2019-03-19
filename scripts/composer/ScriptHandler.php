<?php

/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */

namespace DrupalProject\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ScriptHandler {


    public static function removeFiles(Event $event) {
        $fs = new Filesystem();
        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());
        $drupalRoot = $drupalFinder->getDrupalRoot();
        $event->getIO()->write($drupalRoot);

        if ($fs->exists($drupalRoot . '/core/COPYRIGHT.txt')) {
            $fs->remove($drupalRoot . '/core/COPYRIGHT.txt');
            $event->getIO()->write("Removing COPYRIGHT.txt");
        }

        if ($fs->exists($drupalRoot . '/core/CHANGELOG.txt')) {
            $fs->remove($drupalRoot . '/core/CHANGELOG.txt');
            $event->getIO()->write("Removing CHANGELOG.txt");
        }

        if ($fs->exists($drupalRoot . '/core/INSTALL.mysql.txt')) {
            $fs->remove($drupalRoot . '/core/INSTALL.mysql.txt');
            $event->getIO()->write("Removing INSTALL.mysql.txt");
        }

        if ($fs->exists($drupalRoot . '/core/INSTALL.pgsql.txt')) {
            $fs->remove($drupalRoot . '/core/INSTALL.pgsql.txt');
            $event->getIO()->write("Removing INSTALL.pgsql.txt");
        }

        if ($fs->exists($drupalRoot . '/core/INSTALL.sqlite.txt')) {
            $fs->remove($drupalRoot . '/core/INSTALL.sqlite.txt');
            $event->getIO()->write("Removing INSTALL.sqlite.txt");
        }

        if ($fs->exists($drupalRoot . '/core/INSTALL.txt')) {
            $fs->remove($drupalRoot . '/core/INSTALL.txt');
            $event->getIO()->write("Removing INSTALL.sqlite.txt");
        }

        if ($fs->exists($drupalRoot . '/core/LICENSE.txt')) {
            $fs->remove($drupalRoot . '/core/LICENSE.txt');
            $event->getIO()->write("Removing core/LICENSE.txt");
        }

        if ($fs->exists($drupalRoot . '/core/MAINTAINERS.txt')) {
            $fs->remove($drupalRoot . '/core/MAINTAINERS.txt');
            $event->getIO()->write("Removing core/MAINTAINERS.txt");
        }

    }

    public static function moveBootstrap(Event $event) {
        $fs = new Filesystem();
        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());
        $drupalRoot = $drupalFinder->getDrupalRoot();
        $event->getIO()->write($drupalRoot);
        if ($fs->exists($drupalRoot . '/../vendor/twbs/bootstrap-sass')) {
            $fs->mirror($drupalRoot . '/../vendor/twbs/bootstrap-sass',$drupalRoot . '/libraries/bootstrap-sass');
        }
    }


  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    $dirs = [
      'libraries',
      'modules',
      'profiles',
      'themes',
      'patches',
      '../private_files',
      '../temp_files',
      '../keys',
      '../config',
      '../config/vhosts',
      '../config/sync'
    ];

    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/'. $dir)) {
        $fs->mkdir($drupalRoot . '/'. $dir);
        $fs->touch($drupalRoot . '/'. $dir . '/.gitkeep');
      }
    }


    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') and $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/sync', $drupalRoot),
          'required' => TRUE,
        ],
      ];
      drupal_rewrite_settings($settings, $drupalRoot . '/sites/default/settings.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.php file with chmod 0666");
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Create a sites/default/files directory with chmod 0777");
    }
  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }

}
