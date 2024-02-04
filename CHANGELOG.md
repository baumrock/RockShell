# [2.3.0](https://github.com/baumrock/RockShell/compare/v2.2.0...v2.3.0) (2024-02-04)


### Bug Fixes

* install command broken ([343b795](https://github.com/baumrock/RockShell/commit/343b7959366123c6832d6f73a4a54390932b7b3e))


### Features

* add backtrace() for cli debugging ([5c23d24](https://github.com/baumrock/RockShell/commit/5c23d24d9838c60480c4a60a808f609e4840e413))
* add files:cleanup ([a242249](https://github.com/baumrock/RockShell/commit/a242249646bbf99860c77a85f43fa30b3f393e4b))
* add user:reset command ([6e34d96](https://github.com/baumrock/RockShell/commit/6e34d960373e25a80bb6ba772f5022828dc10e7d))
* improve install command ([4e6f164](https://github.com/baumrock/RockShell/commit/4e6f1646cbc1301e3d17b8b9a417adc63ee3ff48))
* make commands run as sudo ([02df9ae](https://github.com/baumrock/RockShell/commit/02df9aebec41218b7a4cabfa5139a82cba6f63b7))
* use ddev_approot for localRootPath ([17a372a](https://github.com/baumrock/RockShell/commit/17a372add3b2baf55339327b1c4ed001decd8e5f))



# [2.2.0](https://github.com/baumrock/RockShell/compare/v2.1.1...v2.2.0) (2024-01-03)


### Features

* upgrade dependencies to support PHP8.2 ([ab50736](https://github.com/baumrock/RockShell/commit/ab507365a2cf3aa365b86b8e1c61b43112166bd0))



## [2.1.1](https://github.com/baumrock/RockShell/compare/v2.1.0...v2.1.1) (2023-11-02)


### Bug Fixes

* use choice for site profile instead of completion ([3b6ebd2](https://github.com/baumrock/RockShell/commit/3b6ebd22935f16c5ee1b0f98b335bef9f4927dcb))



# [2.1.0](https://github.com/baumrock/RockShell/compare/v2.0.0...v2.1.0) (2023-08-11)


### Bug Fixes

* command db:pull using old syntax ([52f78e4](https://github.com/baumrock/RockShell/commit/52f78e48d845b76eeebcaf95d0a90e207b7bd3ca))
* install command not working for dotnetic ([2a68b11](https://github.com/baumrock/RockShell/commit/2a68b115a29749086d0c8901fef050749c58bfb1))
* rename rockshell to rock everywhere and update readme ([2697bd6](https://github.com/baumrock/RockShell/commit/2697bd65bdcd4426c70d6bdfb88172f775ec20e3))


### Features

* add DbDownload command ([2e91be7](https://github.com/baumrock/RockShell/commit/2e91be726a335b70f720a63b8859a57350d2a6f3))
* add warning if PHP version is too low ([a8caca8](https://github.com/baumrock/RockShell/commit/a8caca85695cd46899c0ee9fd2f516ce14b7351d))



# [2.0.0](https://github.com/baumrock/RockShell/compare/v1.6.0...v2.0.0) (2023-07-07)


### Bug Fixes

* rename rockshell symlink ([9081b12](https://github.com/baumrock/RockShell/commit/9081b12b2bf9495f5c48e1664786429d87c24c7e))
* wrong filesize in dbdump ([19aa16e](https://github.com/baumrock/RockShell/commit/19aa16e6098ed7e74c6f348b98cb343351dd1699))


### Features

* add hello world command ([092ed74](https://github.com/baumrock/RockShell/commit/092ed74184e645cc81de7b17f1375c6add27eca2))
* add Symlink command ([c664ec6](https://github.com/baumrock/RockShell/commit/c664ec69b3eb32d1184a85dc0349f538622d3904))
* improve loading of commands ([752d342](https://github.com/baumrock/RockShell/commit/752d34211ff59415f5bf1cc73e821801978fea33))
* use colon instead of hyphen ([1491196](https://github.com/baumrock/RockShell/commit/14911963b8b1eadaaadc8ea0f6d060fd556c6049))


### BREAKING CHANGES

* change command syntax from hyphen to colon



