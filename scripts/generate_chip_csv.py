import sqlite3
import sys
import getopt

def usage():
    print "Usage: " + sys.argv[0] + "-f <path_to_sqlite_file>"

def main():
    try:
        opts, args = getopt.getopt(sys.argv[1:], "hf:", ["help", "file="])
    except getopt.GetoptError as err:
        print str(err)
        usage()
        sys.exit(2)
    file = None
    for option, argument in opts:
        if option in ("-h", "--help"):
            usage()
            sys.exit()
        elif option in ("-f", "--file"):
            file = argument
        else:
            assert False, "Unhandled option"
    generate_csv(file)


def generate_csv(file):
    if (file == None):
        usage()
        sys.exit(2)
    conn = sqlite3.connect(file)
    c = conn.cursor()
    statement = "SELECT fid, meta_fsetid as fsetid FROM pmfeature INNER JOIN " + "core_mps USING(fsetid)"
    num_row = 0
    for row in c.execute(statement):
        num_row = num_row + 1
    print(num_row)

if __name__ == "__main__":
    main()
