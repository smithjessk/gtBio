import sqlite3
import sys
import getopt
import csv
import os

def main():
    try:
        long_opts = ["help", "file=", "output="]
        opts, args = getopt.getopt(sys.argv[1:], "hf:o:", long_opts)
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

def usage():
    options = "-f <path_to_sqlite_file> -o <path_to_output_file>"
    help_statement = "Usage: " + sys.argv[0] + options
    print help_statement

def get_query_result(file_path):
    conn = sqlite3.connect(file_path)
    c = conn.cursor()
    statement = "SELECT fid, meta_fsetid as fsetid FROM pmfeature " + \
        "INNER JOIN core_mps USING(fsetid)"
    return c.execute(statement)

def get_ordered_fsetid(ordered_fsetid_map, fsetid):
    if fsetid in ordered_fsetid_map:
            ordered_fsetid_map[fsetid] = ordered_fsetid_map[fsetid] + 1
    else:
        ordered_fsetid_map[fsetid] = 1
    return ordered_fsetid_map[fsetid]

def write(file_path, csv_writer):
    csv_writer.writerow(["ordered_fid", "ordered_fsetid", "fid", "fsetid"])
    ordered_fid = 0
    ordered_fsetid_map = {}
    for row in get_query_result(file_path):
        if len(row) != 2:
            raise Exception("Expected (fid, fsetid) in rows from SQL file")
        fsetid = row[1]
        ordered_fsetid = get_ordered_fsetid(ordered_fsetid_map, fsetid)
        csv_writer.writerow([ordered_fid, ordered_fsetid] + list(row))
        ordered_fid += 1

def generate_csv(file_path, output_path):
    if (file_path == None or output_path == None):
        usage()
        sys.exit(2)
    num_row = 0
    with open(output_path, 'w') as csv_file:
        csv_writer = csv.writer(csv_file)
        write(file_path, csv_writer)
    print "Wrote output to " + output_path

if __name__ == "__main__":
    main()
