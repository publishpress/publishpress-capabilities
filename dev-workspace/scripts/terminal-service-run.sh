#!/usr/bin/env bash

bash ./scripts/services-init-cache.sh

CACHE_NAME_LAST_UPDATE="$CACHE_PATH/.last_image_update_check"
ONE_DAY_IN_SECONDS=86400
UPDATE_CHECK_INTERVAL=$ONE_DAY_IN_SECONDS

run_terminal_service() {
    if [ $# -eq 0 ]; then
        docker compose -f docker/compose.yaml run --rm terminal zsh -lc 'if [ -n "$GIT_USER_NAME" ]; then git config --global user.name "$GIT_USER_NAME"; fi; if [ -n "$GIT_USER_EMAIL" ]; then git config --global user.email "$GIT_USER_EMAIL"; fi; exec zsh'
    else
        docker compose -f docker/compose.yaml run --rm terminal sh -c '
            [ -n "$GIT_USER_NAME" ] && git config --global user.name "$GIT_USER_NAME"
            [ -n "$GIT_USER_EMAIL" ] && git config --global user.email "$GIT_USER_EMAIL"
            exec "$@"
        ' _ "$@"
    fi
}

bash ./scripts/services-pull-images.sh --daily

if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0 [--new|-n|--help|-h]"
    exit 0
fi

HAS_NO_COMMAND=true
for arg in "$@"; do
    if [ "$arg" = "--new" ] || [ "$arg" = "-n" ]; then
        HAS_NO_COMMAND=false
        break
    fi
done

if [ "$1" = "--new" ] || [ "$1" = "-n" ]; then
    if [ "$HAS_NO_COMMAND" = false ]; then
        echo "Running new container"
    fi
    run_terminal_service "${@:2}"
elif [ -z "$RUNNING_CONTAINER" ]; then
    if [ "$HAS_NO_COMMAND" = false ]; then
        echo "Running new container"
    fi
    run_terminal_service "$@"
fi
