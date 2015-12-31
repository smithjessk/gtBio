import sqlite3
import sys
import getopt
import csv
import os

def usage():
    print "Usage: " + sys.argv[0] + "-f <path_to_sqlite_file> -o <path_to_output_file>"

def main():
    try:
        opts, args = getopt.getopt(sys.argv[1:], "hf:o:", ["help", "file=", "output="])
    except getopt.GetoptError as err:
        print str(err)
        usage()
        sys.exit(2)
    file_path = None
    output_path = None
    for option, argument in opts:
        if option in ("-h", "--help"):
            usage()
            sys.exit()
        elif option in ("-f", "--file"):
            file_path = argument
        elif option in ("-o", "--output"):
            output_path = argument
        else:
            assert False, "Unhandled option"
    generate_csv(file_path, output_path)

def generate_csv(file_path, output_path):
    if (file_path == None or output_path == None):
        usage()
        sys.exit(2)
    conn = sqlite3.connect(file_path)
    c = conn.cursor()
    statement = "SELECT fid, meta_fsetid as fsetid FROM pmfeature INNER JOIN " + "core_mps USING(fsetid)"
    num_row = 0
    with open(output_path, 'w') as csv_file:
        csv_writer = csv.writer(csv_file)
        csv_writer.writerow(["fid", "fsetid"])
        for row in c.execute(statement):
            csv_writer.writerow(row)
    print "Wrote output to " + output_path

if __name__ == "__main__":
    main()
