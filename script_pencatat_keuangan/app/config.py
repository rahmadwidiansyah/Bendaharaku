import os

APP_HOST = os.getenv("AI_PARSER_HOST", "0.0.0.0")
APP_PORT = int(os.getenv("AI_PARSER_PORT", "3987"))
APP_DEBUG = os.getenv("AI_PARSER_DEBUG", "false").lower() == "true"
