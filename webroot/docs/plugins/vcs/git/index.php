<?php
    $section = 'vcs-plugins';
    $subpage = 'ref-plugin-git';
    $title = 'Git Synchronization Plugin';
    include($_SERVER['DOCUMENT_ROOT'] . '/../inc/documentation-header.php');

    include($_SERVER['DOCUMENT_ROOT'] . '/../inc/version-selector.php');
?>

<h1><?php echo htmlspecialchars($title) ?></h1>

<p>Plugin source location: <code>&lt;serge_root&gt;/lib/Serge/Sync/Plugin/VCS/git.pm</code></p>

<p>This plugin provides integration with <a href="https://git-scm.com/">Git</a>-based source code repositories. On <code><a href="/docs/help/serge-pull/">pull</a></code> sync step, Serge will update its local checkout from your Git server. Respectively, on <code><a href="/docs/help/serge-push/">push</a></code> sync step, Serge will push all the updated files back to the remote repository.</p>

<p>Communication between Serge and Git is performed by the means of running <code>git</code> command-line tool. This means that Git must be installed on the same machine as Serge and have authentication properly configured.</p>

<p>Each configuration file in Serge represents a single translation project, and maps to one or more remote source code repositories (in case of multiple repositories, they all need to be under the same version control and have the same committer configured, since VCS plugin name and committer username are shared within a configuration file). The typical workflow is this:</p>

<ol>
    <li>Create a root directory that will hold all your checkouts, e.g. <code>/var/serge/data/</code></li>
    <li>Create a new Serge configuration file (let's call it <code>my_project.serge</code>) for your translation project so that it stores local repository files under <code>/var/serge/data/my_project/</code> (see <code>sync &rarr; vcs &rarr; data &rarr; local_path</code> parameter)</li>
    <li>Run <code>serge pull --initialize my_project.serge</code> the first time to do the initial checkout; check that <code>/var/serge/data/my_project/</code> folder contains proper files</li>
    <li>To test if you have proper write permissions, alter or add some file in the local repository and run <code>serge push my_project.serge --message="test"</code>; check that your commit went through to the remote server</li>
</ol>

<p>Later you will run <code>serge sync</code> continuously against this configuration file, which will perform the two-way sync between Serge and Git among other synchronization/localization steps. See <a href="/docs/localization-cycle/">Localization Cycle</a> for more information.</p>

<h2>Usage</h2>

<figure>
    <figcaption>example-project.serge</figcaption>
    <script language="text/x-config-neat">
sync
{
    vcs
    {
        plugin                   git

        data
        {
            # (STRING) Absolute path to local folder where local
            # checkout will be stored.
            local_path           /var/serge/data/my_project

            # (STRING) Path to a single remote repository
            # to sync with local `data_dir` folder
            remote_path          ssh://l10n@git.example.com/myrepo
            # --- OR ---
            # (MAP) A key-value list of local subfolders to create and
            # their corresponding remote repositories (if the localizable
            # data for the single localization project is located
            # in several per-component or per-library repositories)
            remote_path
            {
                # one can specify branch name after the '#'.
                # below, the `v5` branch us used
                main             ssh://l10n@git.example.com/myapp#v5
                # if no branch is specified, default repo branch is used
                # (typically, `master`)
                widget           ssh://l10n@git.example.com/mywidget
            }

            # (BOOLEAN) [OPTIONAL] should the newly generated
            # files be added to the remote repository automatically?
            # (YES or NO, defaults to NO)
            add_unversioned      NO

            # (STRING) [OPTIONAL] Commit message
            # Default: 'Automatic commit of updated project files'
            commit_message       Automatic commit of updated project files

            # (STRING) public committer name
            name                 L10N Robot

            # (STRING) committer's email address
            email                l10n-robot@example.com

            # (STRING) [OPTIONAL] additional parameters to be used
            # in `git clone` command at project initialization
            # (when `serge pull --initialize` is run). An example below
            # tells cloning to be shallow (which can speed up cloning
            # projects with extensive history)
            # Default: empty string
            clone_params         --depth 1 --no-tags

            # (STRING) [OPTIONAL] a command to be invoked to clone
            # a repository. When specified, the `clone_params` parameter
            # is ignored, and the internal `git clone <...>` command
            # invocation is replaced with the provided one.
            # This is useful to run complex cloning scripts
            # that work better with monorepos, or to set up specific
            # repository properties to support, for example, Git LFS.
            # Before the command is executed, the local checkout directory
            # is pre-created and set as a current working directory,
            # and the following environment variables are set:
            #   GIT_LOCAL  => target local checkout directory
            #   GIT_REMOTE => remote URL (as parsed from `remote_path`)
            #   GIT_BRANCH => branch name (as parsed from `remote_path`)
            # Default: empty string
            clone_command        ./clone-monorepo.sh lfs/include/path

            # (STRING) [OPTIONAL] additional parameters to be used
            # in `git fetch` command during the update of a local project
            # repository (during `serge pull` step). An example below
            # tells fetch to be shallow
            # Default: empty string
            fetch_params         --depth 1 --no-tags

            # (STRING) [OPTIONAL] additional parameters to be used
            # in `git commit` command when changes are committed
            # to the local repository (during `serge push` step).
            # An example below tells commit to bypass the pre-commit
            # and commit-msg hooks
            # Default: empty string
            commit_params        --no-verify

            # (STRING) [OPTIONAL] additional parameters to be used
            # in `git push` command when changes are pushed
            # to the remote origin (during `serge push` step).
            # An example below tells commit to bypass the pre-push
            # hook
            # Default: empty string
            push_params          --no-verify
        }
    }

    # other sync parameters
    # ...
}
</script>
</figure>

<p>The following script, if used in <code>clone_command</code>, is identical to the default behavior: it clones a remote repo into a target local directory. It can be a good starting point for you to experiment with custom cloning scripts.</p>

<figure>
    <figcaption>clone-default.sh</figcaption>
    <script language="text/x-config-neat">
#!/bin/sh

echo "Initializing local checkout..."
echo "GIT_LOCAL : $GIT_LOCAL"
echo "GIT_REMOTE: $GIT_REMOTE"
echo "GIT_BRANCH: $GIT_BRANCH"

# set up the repository
# (current working directory is already set
# to the proper pre-created local folder)
git clone $GIT_REMOTE --branch $GIT_BRANCH .
</script>
</figure>

<p>The following script initializes LFS, enables sparse checkout, and uses an external parameter passed from the config file.</p>

<figure>
    <figcaption>clone-monorepo.sh</figcaption>
    <script language="text/x-config-neat">
#!/bin/sh

LFS_INCLUDE_PATH=$1

echo "Initializing local monorepo checkout..."
echo "LFS_INCLUDE_PATH : $LFS_INCLUDE_PATH"
echo "GIT_LOCAL        : $GIT_LOCAL"
echo "GIT_REMOTE       : $GIT_REMOTE"
echo "GIT_BRANCH       : $GIT_BRANCH"

# set up the repository
# (current working directory is already set
# to the proper pre-created local folder)
git init
git lfs install
git remote add origin "$GIT_REMOTE"
git config core.sparsecheckout true
git config lfs.fetchinclude "$LFS_INCLUDE_PATH"
echo "$LFS_INCLUDE_PATH" >> .git/info/sparse-checkout

# do an initial fetch/checkout of just one branch
# with no tags and with a history depth of 1
git fetch origin --no-tags --depth=1 \
    +refs/heads/$GIT_BRANCH:refs/remotes/origin/$GIT_BRANCH
git checkout $GIT_BRANCH
</script>
</figure>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/../inc/documentation-footer.php') ?>

