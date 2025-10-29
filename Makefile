# Version file
VERSION_FILE = version
CURRENT_VERSION = $(shell cat $(VERSION_FILE) 2>/dev/null || echo "0.1.0")

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

.PHONY: help release test

## Show help
help:
	@echo '\n${YELLOW}Available commands:${RESET}'
	@echo '${YELLOW}------------------${RESET}'
	@echo '${GREEN}make release${RESET}  - Create a new release'
	@echo '${GREEN}make test${RESET}    - Run tests'
	@echo '${GREEN}make version${RESET} - Show current version'

## Show current version
version:
	@if [ -f "$(VERSION_FILE)" ]; then \
		echo "${GREEN}Current version:${RESET} $(CURRENT_VERSION)"; \
	else \
		echo "${YELLOW}Version file not found. Creating with initial version 0.1.0${RESET}"; \
		echo "0.1.0" > $(VERSION_FILE); \
	fi

## Run tests
test:
	@echo "${YELLOW}Running tests...${RESET}"
	@composer test || (echo "${YELLOW}Tests failed! Fix the issues before creating a new release.${RESET}" && exit 1)

## Create a new release
release: test
	@if [ ! -f "$(VERSION_FILE)" ]; then \
		echo "0.1.0" > $(VERSION_FILE); \
	fi
	@echo -n "Current version is $(CURRENT_VERSION). Enter new version: "; \
	read new_version; \
	version_no_v=$$(echo "$$new_version" | sed 's/^v//'); \
	if ! echo "$$version_no_v" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$$'; then \
		echo "${YELLOW}Invalid version format. Use X.Y.Z (e.g., 1.0.0)${RESET}"; \
		exit 1; \
	fi; \
	echo "$$version_no_v" > $(VERSION_FILE); \
	echo "${GREEN}Version updated to $$version_no_v${RESET}"; \
	echo -n "Enter commit message (or press Enter to use default): "; \
	read commit_message; \
	if [ -z "$$commit_message" ]; then \
		commit_message="Bump version to $$version_no_v"; \
	fi; \
	git add .; \
	git commit -m "$$commit_message"; \
	if git push origin main; then \
		echo "${YELLOW}Creating and pushing tag v$$version_no_v...${RESET}"; \
		if git tag -a v$$version_no_v -m "Release v$$version_no_v" && git push origin v$$version_no_v; then \
			echo "${GREEN}Tag v$$version_no_v created and pushed successfully!${RESET}"; \
			echo "${GREEN}Release process completed successfully!${RESET}"; \
			exit 0; \
		else \
			echo "${YELLOW}Failed to create or push tag.${RESET}"; \
			exit 1; \
		fi; \
	else \
		echo "${YELLOW}Failed to push changes to main.${RESET}"; \
		echo "${YELLOW}Changes were committed but not pushed.${RESET}"; \
		git status; \
		exit 1; \
	fi
