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
        in
        {
          packages = {
            default = pkgs.php.buildComposerProject (finalAttrs: {
              pname = "apposto";
              version = "1.0.0";
              src = ./.;
              vendorHash = "sha256-ezHlbARjnQqq3XJyjvjHeZTQzuSJ2wY5ZNxIMTVhAzE=";
              postInstall = ''
                cd $out/share/php/apposto
                php artisan livewire:publish --assets
                php artisan storage:link
              '';

              nativeBuildInputs = [ pkgs.php ];
            });

            containerImage = let appRoot = "/share/php/apposto"; in
              nix2containerPkgs.nix2container.buildImage {
                name = "apposto";

                copyToRoot = pkgs.buildEnv {
                  name = "image-root";
                  paths = [ self.packages.${system}.default pkgs.bashInteractive pkgs.coreutils pkgs.curl ];
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
                  languages.php.enable = true;
                  packages = [
                    pkgs.actionlint
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
