{
  inputs = {
    flake-utils.url = "github:numtide/flake-utils";
    nix2container = {
      url = "github:nlewo/nix2container";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
    devenv = {
      url = "github:cachix/devenv";
      inputs.nixpkgs.follows = "nixpkgs";
    };
  };

  outputs = { devenv, flake-utils, nix2container, nixpkgs, self } @ inputs:
    flake-utils.lib.eachDefaultSystem
      (system:
        let
          pkgs = import nixpkgs { inherit system; };
          nix2containerPkgs = nix2container.packages.${system};
          # Commit marker: makes derivation unique per commit in CI (GITHUB_SHA).
          # Enables correct cache invalidation while keeping cache for deps (nixpkgs, php).
          commitSha = builtins.getEnv "GITHUB_SHA";
          commitMarker = pkgs.runCommand "commit-marker" { } ''
            echo -n "${commitSha}" > $out
          '';
          appSrc = pkgs.runCommand "apposto-src" { } ''
            cp -r ${./.} $out
            chmod -R u+w $out
            cp ${commitMarker} $out/.nix-commit
          '';
        in
        {
          packages = {
            default = pkgs.php83.buildComposerProject (finalAttrs: {
              pname = "apposto";
              version = "1.0.0";
              src = appSrc;
              vendorHash = "sha256-FHfwLwSa2VGamxKRNmQ2UADmHA0ApzdX7L0fBV2eeXs=";
              postInstall = ''
                cd $out/share/php/apposto
                php artisan livewire:publish --assets
                php artisan storage:link
              '';

              nativeBuildInputs = [ pkgs.php83 ];
            });

            containerImage = let appRoot = "/share/php/apposto"; in
              nix2containerPkgs.nix2container.buildImage {
                name = "apposto";

                copyToRoot = pkgs.buildEnv {
                  name = "image-root";
                  paths = [ self.packages.${system}.default pkgs.php83 pkgs.bashInteractive pkgs.coreutils pkgs.curl ];
                };

                perms = [{
                  path = self.packages.${system}.default;
                  regex = "^${self.packages.${system}.default}${appRoot}/database$";
                  mode = "755";
                  uid = 1000;
                  gid = 1000;
                }];

                config = {
                  Cmd = [ "serve" ];
                  Entrypoint = [ "${appRoot}/artisan" ];
                  Env = [
                    "SSL_CERT_FILE=${pkgs.cacert}/etc/ssl/certs/ca-bundle.crt"
                  ];
                  ExposedPorts = {
                    "8000" = { };
                  };
                  User = "1000:1000";
                  Volumes = {
                    "${appRoot}/bootstrap/cache" = { };
                    "${appRoot}/database/database.sqlite" = { };
                    "${appRoot}/storage" = { };
                  };
                  WorkingDir = appRoot;
                };
              };
          };

          devShells = {
            default = devenv.lib.mkShell {
              inherit inputs pkgs;
              modules = [
                ({ config, ... }: {
                  languages.php = {
                    enable = true;
                    version = "8.3";
                  };
                  packages = [
                    pkgs.actionlint
                    pkgs.azure-cli
                    pkgs.helm-docs
                    pkgs.kubectl
                    pkgs.kubelogin
                    pkgs.kubernetes-helm
                  ];
                  processes.serve.exec = "./artisan serve";
                })
              ];
            };
          };
        });
}
