from git_filter_repo import Commit

COPILOT = b"Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"

def commit_callback(commit: Commit):
    if commit.message:
        lines = commit.message.splitlines()

        filtered = []
        for line in lines:
            if line.strip() == COPILOT:
                continue
            filtered.append(line)

        # Hilangkan newline kosong di akhir commit message
        while len(filtered) >= 2 and filtered[-1] == b"" and filtered[-2] == b"":
            filtered.pop()

        commit.message = b"\n".join(filtered)

