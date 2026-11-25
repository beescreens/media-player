#!/usr/bin/env bash

## Configure Bash aliases
tee -a ~/.bash_aliases > /dev/null <<"EOF"
alias tree='tree --dirsfirst -A -F'
alias jpegoptim='jpegoptim --strip-all --all-progressive'
alias optipng='optipng -o5 -strip all -fix'
EOF

## Enable globstar option for recursive globbing
tee -a ~/.bashrc > /dev/null <<"EOF"
shopt -s globstar nullglob
EOF

## Install required packages
# Update packages list
sudo apt update

# Install packages to optimize images (jpegoptim, optipng)
sudo apt install --yes jpegoptim optipng

# Install packages to optimize documents (ps2pdf)
sudo apt install --yes ghostscript

# Install network utilities (dig, nslookup, ping, ncat)
sudo apt install --yes dnsutils iputils-ping ncat

## Git configuration
# Set pull to rebase by default
git config pull.rebase true

# Enable rerere to automatically resolve conflicts that have been resolved before
git config rerere.enabled true

# Configure git to use force push when including changes
git config push.useForceIfIncludes true
