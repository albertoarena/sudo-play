#!/usr/bin/env bash
#
# Sourced by release.sh. `retry <command...>` runs the command up to five
# times, with a pause between tries, and returns its exit code when the last
# try fails too.
#
# `xcrun stapler` asks Apple's ticket service online, and on GitHub's runner
# that connection drops now and then (error 68). The work is already done by
# then, so another try is all it needs.
#
#   SUDO_PLAY_RETRY_PAUSE  seconds between tries, 15 by default

retry() {
    local tries=5 pause="${SUDO_PLAY_RETRY_PAUSE:-15}" attempt=1 status
    while true; do
        status=0
        "$@" || status=$?
        [ "$status" -eq 0 ] && return 0
        if [ "$attempt" -ge "$tries" ]; then
            echo "Failed $tries times: $*" >&2
            return "$status"
        fi
        echo "Try $attempt of $tries failed with $status, trying again in ${pause}s: $*" >&2
        attempt=$((attempt + 1))
        sleep "$pause"
    done
}
