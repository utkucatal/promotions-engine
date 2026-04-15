# as root on Ops server:

# 1. Docker Engine
curl -fsSL https://get.docker.com | sh

# 2. doctl
curl -sL https://github.com/digitalocean/doctl/releases/download/v1.104.0/doctl-1.104.0-linux-amd64.tar.gz | tar xz -C /tmp
mv /tmp/doctl /usr/local/bin/doctl

# 3. kubectl
curl -sL "https://dl.k8s.io/release/$(curl -sL https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl" -o /usr/local/bin/kubectl
chmod +x /usr/local/bin/kubectl

# 4. helm
curl -sL https://raw.githubusercontent.com/helm/helm/main/scripts/get-helm-3 | bash

# 5. k6
curl -sL https://github.com/grafana/k6/releases/download/v0.54.0/k6-v0.54.0-linux-amd64.tar.gz | tar xz -C /tmp
mv /tmp/k6-v0.54.0-linux-amd64/k6 /usr/local/bin/k6

# check
doctl version && kubectl version --client && helm version --short && k6 version && docker --version
