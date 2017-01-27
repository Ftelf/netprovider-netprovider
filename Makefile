APPLICATION_SOURCE_PATH = .
APPLICATION_LOCALES = en_US.UTF-8 cs_CZ.UTF-8


# ==============================================================================
# Compute locales
# ==============================================================================

ifndef APPLICATION_LOCALES
  COMPUTED_LOCALES = C
else
  COMPUTED_LOCALES = C $(APPLICATION_LOCALES)
endif

CMD_LINE = echo "----------------------------------------------------------------------------"






################################################################################
# COMMON TARGETS
################################################################################

locales:
	@echo
	@echo "  PREPARING APPLICATION TRANSLATION"
	@$(CMD_LINE)
	@echo "  * Processing source code..."

	@which xgettext > /dev/null 2>&1 || (echo "    - Please install gettext tools (xgettext)" && exit 1)
	@which msginit > /dev/null 2>&1 || (echo "    - Please install gettext tools (msginit)" && exit 1)
	@which msgmerge > /dev/null 2>&1 || (echo "    - Please install gettext tools (msgmerge)" && exit 1)

	@mkdir -p $(APPLICATION_SOURCE_PATH)/translation

	@rm -f $(APPLICATION_SOURCE_PATH)/translation/messages.pot
	@touch $(APPLICATION_SOURCE_PATH)/translation/messages.pot
	xgettext --language=PHP --no-wrap --from-code=UTF-8 \
	  --sort-by-file --add-comments=TRANSLATION \
	  -o $(APPLICATION_SOURCE_PATH)/translation/messages.pot \
	 `find $(APPLICATION_SOURCE_PATH) -name "*.php"`

	@echo "  * Processing translations..."
	@for LOC in $(COMPUTED_LOCALES); do \
	  echo "    - Translation: $$LOC"; \
	  mkdir -p $(APPLICATION_SOURCE_PATH)/translation/$$LOC; \
	  mkdir -p $(APPLICATION_SOURCE_PATH)/translation/$$LOC/LC_MESSAGES; \
	  if [ ! -r $(APPLICATION_SOURCE_PATH)/translation/$$LOC/LC_MESSAGES/messages.po ]; then \
  	    echo "      - Generating initial translation file..."; \
	    msginit --no-wrap --locale $$LOC --no-translator -i $(APPLICATION_SOURCE_PATH)/translation/messages.pot -o $(APPLICATION_SOURCE_PATH)/translation/$$LOC/LC_MESSAGES/messages.po > /dev/null 2>&1; \
	  else \
	    echo "      - Merging translation file..."; \
	    msgmerge --no-wrap --update -q $(APPLICATION_SOURCE_PATH)/translation/$$LOC/LC_MESSAGES/messages.po $(APPLICATION_SOURCE_PATH)/translation/messages.pot; \
	  fi; \
	  echo "      - Generating binary..."; \
	  msgfmt $(APPLICATION_SOURCE_PATH)/translation/$$LOC/LC_MESSAGES/messages.po -o $(APPLICATION_SOURCE_PATH)/translation/$$LOC/LC_MESSAGES/messages.mo; \
	  echo "      - Done..."; \
	done
