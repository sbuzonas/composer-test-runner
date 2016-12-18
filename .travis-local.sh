#!/usr/bin/env bash

travis compile | sed "s/--branch\\\=\\\'\\\'/--branch\\\=\\\'$(git name-rev --name-only HEAD)\\\'/" > .travis-build.sh

travis_cache="${XDG_CACHE_HOME:-$HOME/.cache}/travis-build"
git_head=$(git name-rev --name-only HEAD 2>/dev/null)
git_remote=$(git config --get branch.$git_head.remote 2>/dev/null)
git_remote=${git_remote:-origin}
git_info=$(git ls-remote --get-url $git_remote 2>/dev/null)
build_slug=$(echo $git_info | perl -pe 's#^.*(?:/|:)(.+/.+?)(\.git)?$#\1#')

docker run --rm -it -u travis \
       -v $(pwd)/.travis-build.sh:/travis-run.sh \
       -v $travis_cache/$build_slug/.composer:/home/travis/.composer \
       quay.io/travisci/travis-php /bin/bash /travis-run.sh
