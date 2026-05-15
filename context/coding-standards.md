all post request must have its would Request Class 
try to break functionality into actions that are small and can be reused easily 
all models should have their own Repositories where database queries live.
all route group or related routes must be in a different file and required_once in the api routes file.
all third party services must live its own class and should be located in Service folder \
the goal is to keep controllers simple break features/functionalities into actions so that they can be easily tested and extended.